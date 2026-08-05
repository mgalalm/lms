<?php

namespace Tests\Feature;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Jobs\CreateZoomMeetingJob;
use App\Jobs\DownloadZoomRecordingJob;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\Zoom\ZoomClient;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassSessionZoomIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config(['services.zoom.webhook_secret' => 'test-secret']);
    }

    public function test_creating_an_online_session_dispatches_the_meeting_creation_job(): void
    {
        Queue::fake();

        $session = ClassSession::factory()->create(['type' => SessionType::Online]);

        Queue::assertPushed(CreateZoomMeetingJob::class, fn (CreateZoomMeetingJob $job) => $job->classSession->is($session));
    }

    public function test_creating_a_session_with_an_existing_meeting_id_does_not_redispatch(): void
    {
        Queue::fake();

        ClassSession::factory()->create([
            'type' => SessionType::Online,
            'zoom_meeting_id' => 'already-created',
        ]);

        Queue::assertNotPushed(CreateZoomMeetingJob::class);
    }

    public function test_meeting_creation_job_populates_zoom_fields_from_the_api_response(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
            'api.zoom.us/v2/users/*/meetings' => Http::response([
                'id' => 123456789,
                'join_url' => 'https://zoom.us/j/123456789',
                'start_url' => 'https://zoom.us/s/123456789?zak=abc',
                'password' => 'abc123',
            ]),
        ]);

        // Observer dispatches CreateZoomMeetingJob synchronously (QUEUE_CONNECTION=sync in tests).
        $session = ClassSession::factory()->create(['type' => SessionType::Online]);

        $session->refresh();

        $this->assertSame('123456789', $session->zoom_meeting_id);
        $this->assertSame('https://zoom.us/j/123456789', $session->zoom_join_url);
        $this->assertSame('https://zoom.us/s/123456789?zak=abc', $session->zoom_start_url);
        $this->assertSame('abc123', $session->zoom_password);
    }

    public function test_meeting_creation_job_skips_teachers_without_a_zoom_user_id(): void
    {
        $teacher = User::factory()->teacher()->create();
        $teacher->teacherProfile->update(['zoom_user_id' => null]);

        $session = ClassSession::factory()->for($teacher, 'teacher')->create(['type' => SessionType::Online]);

        $session->refresh();

        $this->assertNull($session->zoom_meeting_id);
    }

    public function test_url_validation_handshake_is_answered_without_requiring_a_signature(): void
    {
        $response = $this->postJson('/webhooks/zoom', [
            'event' => 'endpoint.url_validation',
            'payload' => ['plainToken' => 'abc123plain'],
        ]);

        $response->assertOk()->assertJson([
            'plainToken' => 'abc123plain',
            'encryptedToken' => hash_hmac('sha256', 'abc123plain', 'test-secret'),
        ]);
    }

    public function test_webhook_rejects_requests_with_an_invalid_signature(): void
    {
        $response = $this->postJson('/webhooks/zoom', [
            'event' => 'meeting.ended',
            'payload' => ['object' => ['id' => '123']],
        ], [
            'x-zm-signature' => 'v0=invalid',
            'x-zm-request-timestamp' => (string) time(),
        ]);

        $response->assertStatus(401);
    }

    public function test_meeting_ended_webhook_marks_the_session_completed(): void
    {
        $session = ClassSession::factory()->create([
            'zoom_meeting_id' => '999888777',
            'status' => SessionStatus::InProgress,
        ]);

        $this->postSignedZoomWebhook([
            'event' => 'meeting.ended',
            'payload' => ['object' => ['id' => '999888777']],
        ])->assertNoContent();

        $session->refresh();

        $this->assertSame(SessionStatus::Completed, $session->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_participant_joined_webhook_records_the_teachers_join_time(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['zoom_meeting_id' => '111222333']);

        $this->postSignedZoomWebhook([
            'event' => 'meeting.participant_joined',
            'payload' => [
                'object' => [
                    'id' => '111222333',
                    'participant' => ['email' => $teacher->teacherProfile->zoom_user_id],
                ],
            ],
        ])->assertNoContent();

        $session->refresh();

        $this->assertNotNull($session->teacher_joined_at);
        $this->assertNull($session->student_joined_at);
    }

    public function test_recording_completed_webhook_dispatches_the_download_job(): void
    {
        Queue::fake();

        $session = ClassSession::factory()->create(['zoom_meeting_id' => '444555666']);

        $this->postSignedZoomWebhook([
            'event' => 'recording.completed',
            'payload' => [
                'object' => [
                    'id' => '444555666',
                    'recording_files' => [
                        ['file_type' => 'CHAT', 'download_url' => 'https://zoom.us/rec/download/chat'],
                        ['file_type' => 'MP4', 'download_url' => 'https://zoom.us/rec/download/video'],
                    ],
                ],
            ],
        ])->assertNoContent();

        Queue::assertPushed(
            DownloadZoomRecordingJob::class,
            fn (DownloadZoomRecordingJob $job) => $job->classSession->is($session)
                && $job->downloadUrl === 'https://zoom.us/rec/download/video'
        );
    }

    public function test_download_recording_job_stores_the_file_and_updates_the_session(): void
    {
        Storage::fake('local');
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
            'zoom.us/rec/download/*' => Http::response('fake-video-bytes'),
        ]);

        $session = ClassSession::factory()->create(['type' => SessionType::Physical]);

        (new DownloadZoomRecordingJob($session, 'https://zoom.us/rec/download/video'))
            ->handle(app(ZoomClient::class));

        $session->refresh();

        Storage::disk('local')->assertExists("recordings/{$session->id}.mp4");
        $this->assertSame("recordings/{$session->id}.mp4", $session->recap_path);
    }

    /**
     * Post a Zoom webhook payload signed the way Zoom signs real requests.
     */
    private function postSignedZoomWebhook(array $payload)
    {
        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", 'test-secret');

        return $this->postJson('/webhooks/zoom', $payload, [
            'x-zm-signature' => $signature,
            'x-zm-request-timestamp' => $timestamp,
        ]);
    }
}
