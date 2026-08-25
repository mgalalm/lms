<?php

namespace Tests\Feature;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Jobs\CreateZoomMeetingJob;
use App\Models\ClassSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ClassSessionZoomIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config([
            'services.zoom.webhook_secret' => 'test-secret',
            'services.zoom.host_user_id' => 'host@example.com',
        ]);
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

    public function test_meeting_creation_job_skips_when_no_zoom_host_is_configured(): void
    {
        config(['services.zoom.host_user_id' => null]);

        $session = ClassSession::factory()->create(['type' => SessionType::Online]);

        $session->refresh();

        $this->assertNull($session->zoom_meeting_id);
    }

    public function test_meeting_creation_job_hosts_every_teachers_meeting_under_the_configured_zoom_account(): void
    {
        Http::fake([
            'zoom.us/oauth/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
            'api.zoom.us/v2/users/host@example.com/meetings' => Http::response([
                'id' => 987654321,
                'join_url' => 'https://zoom.us/j/987654321',
                'start_url' => 'https://zoom.us/s/987654321?zak=abc',
                'password' => 'abc123',
            ]),
        ]);

        $teacher = User::factory()->teacher()->create();
        $teacher->teacherProfile->update(['zoom_user_id' => 'unrelated-placeholder@example.org']);

        $session = ClassSession::factory()->for($teacher, 'teacher')->create(['type' => SessionType::Online]);

        $session->refresh();

        $this->assertSame('987654321', $session->zoom_meeting_id);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.zoom.us/v2/users/host@example.com/meetings');
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

    public function test_recording_completed_webhook_records_the_zoom_play_url(): void
    {
        $session = ClassSession::factory()->create(['zoom_meeting_id' => '444555666']);

        $this->postSignedZoomWebhook([
            'event' => 'recording.completed',
            'payload' => [
                'object' => [
                    'id' => '444555666',
                    'recording_files' => [
                        ['file_type' => 'CHAT', 'play_url' => 'https://zoom.us/rec/play/chat'],
                        ['file_type' => 'MP4', 'play_url' => 'https://zoom.us/rec/play/video'],
                    ],
                ],
            ],
        ])->assertNoContent();

        $session->refresh();

        $this->assertSame('https://zoom.us/rec/play/video', $session->zoom_recap_url);
    }

    public function test_recording_completed_webhook_does_nothing_without_an_mp4_file(): void
    {
        $session = ClassSession::factory()->create(['zoom_meeting_id' => '444555666']);

        $this->postSignedZoomWebhook([
            'event' => 'recording.completed',
            'payload' => [
                'object' => [
                    'id' => '444555666',
                    'recording_files' => [
                        ['file_type' => 'CHAT', 'play_url' => 'https://zoom.us/rec/play/chat'],
                    ],
                ],
            ],
        ])->assertNoContent();

        $this->assertNull($session->refresh()->zoom_recap_url);
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
