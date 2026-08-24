<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Jobs\ConvertLessonSlideshowToPdfJob;
use App\Jobs\PushLessonSlideshowToBunnyJob;
use App\Models\Lesson;
use App\Models\LessonSlideshow;
use App\Models\Module;
use App\Models\User;
use App\Services\CloudConvert\CloudConvertClient;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LessonSlideshowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_student_is_denied_access_to_a_slideshow(): void
    {
        Storage::fake('bunny');

        $student = User::factory()->student()->create();
        $slideshow = LessonSlideshow::factory()->create();

        $this->actingAs($student)
            ->getJson(route('lessons.slideshow.playback-token', $slideshow->lesson))
            ->assertForbidden();
    }

    public function test_a_teacher_can_view_a_ready_slideshow(): void
    {
        Storage::fake('bunny');

        $teacher = User::factory()->teacher()->create();
        $slideshow = LessonSlideshow::factory()->create();

        $this->actingAs($teacher)
            ->getJson(route('lessons.slideshow.playback-token', $slideshow->lesson))
            ->assertOk()
            ->assertJsonStructure(['url']);
    }

    public function test_an_admin_can_view_a_ready_slideshow(): void
    {
        Storage::fake('bunny');

        $admin = User::factory()->admin()->create();
        $slideshow = LessonSlideshow::factory()->create();

        $this->actingAs($admin)
            ->getJson(route('lessons.slideshow.playback-token', $slideshow->lesson))
            ->assertOk();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $slideshow = LessonSlideshow::factory()->create();

        $this->get(route('lessons.slideshow.playback-token', $slideshow->lesson))
            ->assertRedirect(route('login'));
    }

    public function test_a_slideshow_still_converting_returns_not_found(): void
    {
        $student = User::factory()->student()->create();
        $slideshow = LessonSlideshow::factory()->pending()->create();

        $this->actingAs($student)
            ->getJson(route('lessons.slideshow.playback-token', $slideshow->lesson))
            ->assertNotFound();
    }

    public function test_a_lesson_with_no_slideshow_returns_not_found(): void
    {
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($student)
            ->getJson(route('lessons.slideshow.playback-token', $lesson))
            ->assertNotFound();
    }

    public function test_the_playback_url_is_freshly_generated_and_expiring_per_request(): void
    {
        Storage::fake('bunny');

        $teacher = User::factory()->teacher()->create();
        $slideshow = LessonSlideshow::factory()->create();

        $first = $this->actingAs($teacher)->getJson(route('lessons.slideshow.playback-token', $slideshow->lesson))->json('url');
        $this->travel(1)->seconds();
        $second = $this->actingAs($teacher)->getJson(route('lessons.slideshow.playback-token', $slideshow->lesson))->json('url');

        $this->assertNotSame($first, $second);
    }

    public function test_the_bunny_push_job_stores_a_pdf_upload_directly(): void
    {
        Storage::fake('local');
        Storage::fake('bunny');
        Storage::disk('local')->put('lesson-slideshow-uploads/deck.pdf', 'fake pdf bytes');

        $slideshow = LessonSlideshow::factory()->pending()->create();

        (new PushLessonSlideshowToBunnyJob($slideshow, 'local', 'lesson-slideshow-uploads/deck.pdf'))->handle();

        $slideshow->refresh();
        $this->assertNotNull($slideshow->file_path);
        Storage::disk('bunny')->assertExists($slideshow->file_path);
        Storage::disk('local')->assertMissing('lesson-slideshow-uploads/deck.pdf');
    }

    public function test_the_conversion_job_converts_a_pptx_upload_via_cloudconvert_then_pushes_to_bunny(): void
    {
        Storage::fake('local');
        Storage::fake('bunny');
        Storage::disk('local')->put('lesson-slideshow-uploads/deck.pptx', 'fake pptx bytes');

        $slideshow = LessonSlideshow::factory()->pending()->create();

        $pdfStream = fopen('php://temp', 'r+');
        fwrite($pdfStream, 'converted pdf bytes');
        rewind($pdfStream);

        $cloudConvert = $this->createMock(CloudConvertClient::class);
        $cloudConvert->expects($this->once())
            ->method('convertToPdf')
            ->willReturn($pdfStream);
        $this->app->instance(CloudConvertClient::class, $cloudConvert);

        (new ConvertLessonSlideshowToPdfJob($slideshow, 'local', 'lesson-slideshow-uploads/deck.pptx'))
            ->handle($this->app->make(CloudConvertClient::class));

        $slideshow->refresh();
        $this->assertNotNull($slideshow->file_path);
        Storage::disk('bunny')->assertExists($slideshow->file_path);
        $this->assertSame('converted pdf bytes', Storage::disk('bunny')->get($slideshow->file_path));
        Storage::disk('local')->assertMissing('lesson-slideshow-uploads/deck.pptx');
    }

    public function test_admin_uploading_a_pdf_dispatches_the_direct_push_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('uploadSlideshow', data: [
                'file' => UploadedFile::fake()->create('deck.pdf', 500, 'application/pdf'),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('lesson_slideshows', ['lesson_id' => $lesson->id, 'locale' => 'en']);
        Queue::assertPushed(PushLessonSlideshowToBunnyJob::class);
        Queue::assertNotPushed(ConvertLessonSlideshowToPdfJob::class);
    }

    public function test_admin_uploading_a_pptx_dispatches_the_conversion_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('uploadSlideshow', data: [
                'file' => UploadedFile::fake()->create(
                    'deck.pptx',
                    500,
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ),
            ])
            ->assertHasNoActionErrors();

        Queue::assertPushed(ConvertLessonSlideshowToPdfJob::class);
        Queue::assertNotPushed(PushLessonSlideshowToBunnyJob::class);
    }

    public function test_the_lms_course_detail_reports_slideshow_readiness(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lessonReady = Lesson::factory()->for($module)->create();
        $lessonPending = Lesson::factory()->for($module)->create();

        LessonSlideshow::factory()->for($lessonReady)->create();
        LessonSlideshow::factory()->pending()->for($lessonPending)->create();

        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn ($page) => $page->component('teacher/Lms/Show')
                ->where('course.modules.0.lessons.0.has_slideshow', true)
                ->where('course.modules.0.lessons.1.has_slideshow', false)
            );
    }
}
