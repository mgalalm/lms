<?php

namespace Tests\Feature\Filament;

use App\Enums\LessonVideoStatus;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Jobs\PushLessonVideoToBunnyJob;
use App\Models\Lesson;
use App\Models\LessonVideo;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LessonVideosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_upload_a_video_which_dispatches_the_bunny_push_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('uploadVideo', data: [
                'file' => UploadedFile::fake()->create('lesson.mp4', 1000, 'video/mp4'),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('lesson_videos', [
            'lesson_id' => $lesson->id,
            'locale' => 'en',
            'status' => LessonVideoStatus::Uploading->value,
            'uploaded_by' => $admin->id,
        ]);

        Queue::assertPushed(PushLessonVideoToBunnyJob::class, fn (PushLessonVideoToBunnyJob $job) => $job->lessonVideo->lesson_id === $lesson->id);
    }

    public function test_publish_action_is_disabled_until_the_video_is_in_draft_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lessonVideo = LessonVideo::factory()->create(['status' => LessonVideoStatus::Processing]);

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lessonVideo->lesson->getRouteKey()])
            ->assertActionDisabled('publishVideo');
    }

    public function test_the_edit_page_shows_a_video_preview_once_pushed_to_bunny(): void
    {
        config(['services.bunny_stream.api_key' => 'test-key', 'services.bunny_stream.library_id' => 1]);

        $admin = User::factory()->admin()->create();
        $lessonVideo = LessonVideo::factory()->processing()->create(['bunny_video_id' => 'bunny-123']);

        $this->actingAs($admin);

        $html = Livewire::test(EditLesson::class, ['record' => $lessonVideo->lesson->getRouteKey()])->html();

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('bunny-123', $html);
    }

    public function test_the_edit_page_shows_no_preview_while_still_uploading(): void
    {
        $admin = User::factory()->admin()->create();
        $lessonVideo = LessonVideo::factory()->create([
            'status' => LessonVideoStatus::Uploading,
            'bunny_video_id' => null,
        ]);

        $this->actingAs($admin);

        $html = Livewire::test(EditLesson::class, ['record' => $lessonVideo->lesson->getRouteKey()])->html();

        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_admin_can_publish_a_draft_video(): void
    {
        $admin = User::factory()->admin()->create();
        $lessonVideo = LessonVideo::factory()->draft()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lessonVideo->lesson->getRouteKey()])
            ->assertActionEnabled('publishVideo')
            ->callAction('publishVideo');

        $lessonVideo->refresh();
        $this->assertSame(LessonVideoStatus::Published, $lessonVideo->status);
        $this->assertNotNull($lessonVideo->published_at);
    }
}
