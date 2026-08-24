<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Lesson;
use App\Models\LessonTutorGuide;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class LessonTutorGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_an_admin_can_create_a_tutor_guide_via_the_lesson_edit_page(): void
    {
        Storage::fake('local');
        Storage::fake('bunny');

        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm([
                'tutor_guide_notion_url' => 'https://www.notion.so/My-Page-83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $guide = $lesson->tutorGuide()->firstOrFail();

        $this->assertSame('83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e', $guide->notion_page_id);
    }

    public function test_the_notion_url_field_rejects_a_non_notion_url(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['tutor_guide_notion_url' => 'https://example.com/not-notion'])
            ->call('save')
            ->assertHasFormErrors(['tutor_guide_notion_url']);
    }

    public function test_the_notion_url_field_accepts_an_app_notion_page_url(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm([
                'tutor_guide_notion_url' => 'https://app.notion.com/p/Welcome-to-Notion-3c668ba0d26f801cbefbc7b864826a2f?source=copy_link',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            '3c668ba0d26f801cbefbc7b864826a2f',
            $lesson->tutorGuide()->firstOrFail()->notion_page_id,
        );
    }

    public function test_a_teacher_can_view_a_tutor_guide(): void
    {
        Storage::fake('local');
        Storage::fake('bunny');

        $teacher = User::factory()->teacher()->create();
        $guide = LessonTutorGuide::factory()->create();
        $media = $guide
            ->addMediaFromString($this->fakePng())
            ->usingFileName('image.png')
            ->toMediaCollection('intro-images');
        $imageUrl = route('lesson-tutor-guides.media', ['lessonTutorGuide' => $guide, 'media' => $media], false);

        $guide->updateIntroMarkdown("Click **Marketplace** in your sidebar\n\n![]({$imageUrl})\n\nCheck out [Notion Academy](https://academy.notion.com/?utm_campaign=gsqw) to level up.");

        $this->actingAs($teacher)
            ->get(route('lessons.tutor-guide', $guide->lesson))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Lms/TutorGuide')
                ->where('introHtml', "<p>Click <strong>Marketplace</strong> in your sidebar</p>\n<p><img src=\"{$imageUrl}\" alt=\"\" /></p>\n<p>Check out <a href=\"https://academy.notion.com/?utm_campaign=gsqw\">Notion Academy</a> to level up.</p>\n")
                ->missing('procedureSteps')
                ->missing('glossaryTerms')
            );
    }

    public function test_a_teacher_can_view_a_tutor_guide_image(): void
    {
        Storage::fake('bunny');

        $teacher = User::factory()->teacher()->create();
        $guide = LessonTutorGuide::factory()->create();
        $media = $guide
            ->addMediaFromString($this->fakePng())
            ->usingFileName('image.png')
            ->toMediaCollection('intro-images');

        $this->actingAs($teacher)
            ->get(route('lesson-tutor-guides.media', ['lessonTutorGuide' => $guide, 'media' => $media]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_a_student_is_denied_access_to_a_tutor_guide_image(): void
    {
        Storage::fake('bunny');

        $student = User::factory()->student()->create();
        $guide = LessonTutorGuide::factory()->create();
        $media = $guide
            ->addMediaFromString($this->fakePng())
            ->usingFileName('image.png')
            ->toMediaCollection('intro-images');

        $this->actingAs($student)
            ->get(route('lesson-tutor-guides.media', ['lessonTutorGuide' => $guide, 'media' => $media]))
            ->assertForbidden();
    }

    public function test_a_tutor_guide_image_must_belong_to_the_requested_guide(): void
    {
        Storage::fake('bunny');

        $teacher = User::factory()->teacher()->create();
        $guide = LessonTutorGuide::factory()->create();
        $otherGuide = LessonTutorGuide::factory()->create();
        $media = $otherGuide
            ->addMediaFromString($this->fakePng())
            ->usingFileName('image.png')
            ->toMediaCollection('intro-images');

        $this->actingAs($teacher)
            ->get(route('lesson-tutor-guides.media', ['lessonTutorGuide' => $guide, 'media' => $media]))
            ->assertNotFound();
    }

    public function test_a_tutor_guide_media_route_only_serves_intro_images(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->teacher()->create();
        $guide = LessonTutorGuide::factory()->create();
        $media = $guide->updateIntroMarkdown('private markdown');

        $this->actingAs($teacher)
            ->get(route('lesson-tutor-guides.media', ['lessonTutorGuide' => $guide, 'media' => $media]))
            ->assertNotFound();
    }

    public function test_a_guest_is_redirected_from_a_tutor_guide_image(): void
    {
        Storage::fake('bunny');

        $guide = LessonTutorGuide::factory()->create();
        $media = $guide
            ->addMediaFromString($this->fakePng())
            ->usingFileName('image.png')
            ->toMediaCollection('intro-images');

        $this->get(route('lesson-tutor-guides.media', ['lessonTutorGuide' => $guide, 'media' => $media]))
            ->assertRedirect(route('login'));
    }

    public function test_an_admin_can_view_a_tutor_guide(): void
    {
        $admin = User::factory()->admin()->create();
        $guide = LessonTutorGuide::factory()->create();

        $this->actingAs($admin)
            ->get(route('lessons.tutor-guide', $guide->lesson))
            ->assertOk();
    }

    public function test_a_student_is_denied_access_even_with_a_direct_route_hit(): void
    {
        $student = User::factory()->student()->create();
        $guide = LessonTutorGuide::factory()->create();

        $this->actingAs($student)
            ->get(route('lessons.tutor-guide', $guide->lesson))
            ->assertForbidden();
    }

    public function test_a_student_is_denied_access_even_when_no_guide_exists_yet(): void
    {
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($student)
            ->get(route('lessons.tutor-guide', $lesson))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $guide = LessonTutorGuide::factory()->create();

        $this->get(route('lessons.tutor-guide', $guide->lesson))
            ->assertRedirect(route('login'));
    }

    public function test_markdown_content_round_trips_through_medialibrary(): void
    {
        $guide = LessonTutorGuide::factory()->create();

        $markdown = "# Title\n\n- one\n- two\n\n> a quote with \"quotes\" and emoji 🎉";
        $guide->updateIntroMarkdown($markdown);

        $this->assertSame($markdown, $guide->fresh()->introMarkdown());
    }

    public function test_a_lesson_with_no_tutor_guide_returns_empty_content_not_an_error(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($teacher)
            ->get(route('lessons.tutor-guide', $lesson))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Lms/TutorGuide')
                ->where('introHtml', null)
                ->missing('procedureSteps')
                ->missing('glossaryTerms')
            );
    }

    private function fakePng(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');
    }
}
