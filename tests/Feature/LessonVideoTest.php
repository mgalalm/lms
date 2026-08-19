<?php

namespace Tests\Feature;

use App\Enums\LessonVideoStatus;
use App\Jobs\PushLessonVideoToBunnyJob;
use App\Models\Lesson;
use App\Models\LessonVideo;
use App\Models\User;
use App\Services\Bunny\BunnyStreamClient;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config(['services.bunny_stream.webhook_secret' => 'test-secret']);
    }

    public function test_a_published_video_is_playable_by_a_guest(): void
    {
        $lessonVideo = LessonVideo::factory()->published()->create(['bunny_video_id' => 'bunny-123']);

        $response = $this->getJson(route('lessons.video.playback-token', $lessonVideo->lesson));

        $response->assertOk();
        $this->assertStringContainsString('bunny-123', $response->json('url'));
    }

    public function test_a_published_video_is_playable_by_a_student(): void
    {
        $lessonVideo = LessonVideo::factory()->published()->create(['bunny_video_id' => 'bunny-123']);
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->getJson(route('lessons.video.playback-token', $lessonVideo->lesson))
            ->assertOk();
    }

    public function test_a_draft_video_is_not_playable_by_a_student_or_a_guest(): void
    {
        $lessonVideo = LessonVideo::factory()->draft()->create(['bunny_video_id' => 'bunny-123']);
        $student = User::factory()->student()->create();

        $this->getJson(route('lessons.video.playback-token', $lessonVideo->lesson))->assertForbidden();

        $this->actingAs($student)
            ->getJson(route('lessons.video.playback-token', $lessonVideo->lesson))
            ->assertForbidden();
    }

    public function test_a_draft_video_is_playable_by_a_teacher_and_an_admin(): void
    {
        $lessonVideo = LessonVideo::factory()->draft()->create(['bunny_video_id' => 'bunny-123']);
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($teacher)
            ->getJson(route('lessons.video.playback-token', $lessonVideo->lesson))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('lessons.video.playback-token', $lessonVideo->lesson))
            ->assertOk();
    }

    public function test_a_lesson_with_no_video_returns_not_found(): void
    {
        $lesson = Lesson::factory()->create();

        $this->getJson(route('lessons.video.playback-token', $lesson))->assertNotFound();
    }

    public function test_the_playback_url_is_freshly_generated_and_expiring_per_request(): void
    {
        $lessonVideo = LessonVideo::factory()->published()->create(['bunny_video_id' => 'bunny-123']);

        $first = $this->getJson(route('lessons.video.playback-token', $lessonVideo->lesson))->json('url');
        $this->travel(1)->seconds();
        $second = $this->getJson(route('lessons.video.playback-token', $lessonVideo->lesson))->json('url');

        $this->assertNotSame($first, $second);
        $this->assertStringContainsString('expires=', $first);
    }

    public function test_the_bunny_upload_job_creates_and_uploads_a_video_then_marks_it_processing(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('lesson-video-uploads/test.mp4', 'fake video bytes');

        $lessonVideo = LessonVideo::factory()->create(['status' => LessonVideoStatus::Uploading, 'bunny_video_id' => null]);

        $bunny = $this->createMock(BunnyStreamClient::class);
        $bunny->expects($this->once())->method('createVideo')->willReturn('new-bunny-guid');
        $bunny->expects($this->once())->method('uploadVideo')->with('new-bunny-guid', 'fake video bytes');
        $this->app->instance(BunnyStreamClient::class, $bunny);

        (new PushLessonVideoToBunnyJob($lessonVideo, 'local', 'lesson-video-uploads/test.mp4'))
            ->handle($this->app->make(BunnyStreamClient::class));

        $lessonVideo->refresh();
        $this->assertSame('new-bunny-guid', $lessonVideo->bunny_video_id);
        $this->assertSame(LessonVideoStatus::Processing, $lessonVideo->status);
        Storage::disk('local')->assertMissing('lesson-video-uploads/test.mp4');
    }

    public function test_the_webhook_marks_a_video_draft_once_encoding_finishes(): void
    {
        $lessonVideo = LessonVideo::factory()->create([
            'status' => LessonVideoStatus::Processing,
            'bunny_video_id' => 'bunny-guid-1',
        ]);

        $bunny = $this->createMock(BunnyStreamClient::class);
        $bunny->method('getVideo')->with('bunny-guid-1')->willReturn([
            'duration_seconds' => 245,
            'thumbnail_url' => 'https://video.bunnycdn.com/library/1/videos/bunny-guid-1/thumb.jpg',
        ]);
        $this->app->instance(BunnyStreamClient::class, $bunny);

        $payload = ['VideoLibraryId' => 1, 'VideoGuid' => 'bunny-guid-1', 'Status' => 4];

        $this->postJson(route('webhooks.bunny'), $payload, $this->signedHeaders($payload))
            ->assertNoContent();

        $lessonVideo->refresh();
        $this->assertSame(LessonVideoStatus::Draft, $lessonVideo->status);
        $this->assertSame(245, $lessonVideo->duration_seconds);
        $this->assertSame('https://video.bunnycdn.com/library/1/videos/bunny-guid-1/thumb.jpg', $lessonVideo->thumbnail_url);
    }

    public function test_the_webhook_rejects_an_invalid_signature(): void
    {
        $payload = ['VideoLibraryId' => 1, 'VideoGuid' => 'bunny-guid-1', 'Status' => 4];

        $this->postJson(route('webhooks.bunny'), $payload, ['x-bunny-signature' => 'not-the-real-signature'])
            ->assertUnauthorized();
    }

    public function test_the_webhook_ignores_a_non_finished_status(): void
    {
        $lessonVideo = LessonVideo::factory()->create([
            'status' => LessonVideoStatus::Processing,
            'bunny_video_id' => 'bunny-guid-1',
        ]);

        $payload = ['VideoLibraryId' => 1, 'VideoGuid' => 'bunny-guid-1', 'Status' => 2];

        $this->postJson(route('webhooks.bunny'), $payload, $this->signedHeaders($payload))
            ->assertNoContent();

        $this->assertSame(LessonVideoStatus::Processing, $lessonVideo->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function signedHeaders(array $payload): array
    {
        $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

        return ['x-bunny-signature' => $signature];
    }
}
