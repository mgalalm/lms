<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Lesson;
use App\Models\LessonSlideshow;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_it_extracts_the_presentation_id_from_a_valid_google_slides_url(): void
    {
        $this->assertSame(
            'abc123XYZ',
            LessonSlideshow::extractPresentationId('https://docs.google.com/presentation/d/abc123XYZ/edit#slide=id.p'),
        );

        $this->assertSame(
            'abc123XYZ',
            LessonSlideshow::extractPresentationId('https://docs.google.com/presentation/d/abc123XYZ/edit'),
        );
    }

    public function test_it_returns_null_for_a_non_google_url(): void
    {
        $this->assertNull(LessonSlideshow::extractPresentationId('https://example.com/presentation/d/abc123XYZ/edit'));
    }

    public function test_it_returns_null_for_a_google_url_that_is_not_a_presentation(): void
    {
        $this->assertNull(LessonSlideshow::extractPresentationId('https://docs.google.com/document/d/abc123XYZ/edit'));
    }

    public function test_it_returns_null_for_a_blank_url(): void
    {
        $this->assertNull(LessonSlideshow::extractPresentationId(null));
        $this->assertNull(LessonSlideshow::extractPresentationId(''));
    }

    public function test_admin_saving_a_valid_google_slides_url_upserts_the_slideshow(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['slideshow_google_url' => 'https://docs.google.com/presentation/d/abc123XYZ/edit'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('lesson_slideshows', [
            'lesson_id' => $lesson->id,
            'locale' => 'en',
            'google_slides_url' => 'https://docs.google.com/presentation/d/abc123XYZ/edit',
            'google_slides_id' => 'abc123XYZ',
        ]);
    }

    public function test_admin_saving_an_invalid_google_slides_url_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['slideshow_google_url' => 'https://example.com/not-a-slideshow'])
            ->call('save')
            ->assertHasFormErrors(['slideshow_google_url']);

        $this->assertDatabaseMissing('lesson_slideshows', ['lesson_id' => $lesson->id]);
    }

    public function test_admin_blanking_the_url_deletes_the_slideshow(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['slideshow_google_url' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('lesson_slideshows', ['lesson_id' => $lesson->id]);
    }

    public function test_the_lms_course_detail_exposes_the_slideshow_embed_id(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lessonWithSlideshow = Lesson::factory()->for($module)->create();
        $lessonWithoutSlideshow = Lesson::factory()->for($module)->create();

        LessonSlideshow::factory()->for($lessonWithSlideshow)->create(['google_slides_id' => 'abc123XYZ']);

        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn ($page) => $page->component('teacher/Lms/Show')
                ->where('course.modules.0.lessons.0.slideshow.google_slides_id', 'abc123XYZ')
                ->where('course.modules.0.lessons.1.slideshow', null)
            );
    }
}
