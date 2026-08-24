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
                'tutor_guide_procedure_steps' => [
                    ['phase' => 'explore', 'title' => 'Introduce sprites', 'duration_minutes' => 10],
                    ['phase' => 'evaluate', 'title' => 'Quick quiz', 'duration_minutes' => 5],
                ],
                'tutor_guide_glossary_terms' => [
                    ['term' => 'Sprite', 'definition' => 'A movable object on the stage.'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $guide = $lesson->tutorGuide()->firstOrFail();

        $this->assertSame('83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e', $guide->notion_page_id);
        $this->assertSame('explore', $guide->procedure_steps[0]['phase']);
        $this->assertSame('Sprite', $guide->glossary_terms[0]['term']);
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
        $teacher = User::factory()->teacher()->create();
        $guide = LessonTutorGuide::factory()->create();
        $guide->updateIntroMarkdown("Click **Marketplace** in your sidebar\n\n![](https://content.example.com/notion/image.png)\n\nCheck out [Notion Academy](https://academy.notion.com/?utm_campaign=gsqw) to level up.");

        $this->actingAs($teacher)
            ->get(route('lessons.tutor-guide', $guide->lesson))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Lms/TutorGuide')
                ->where('introHtml', "<p>Click <strong>Marketplace</strong> in your sidebar</p>\n<p><img src=\"https://content.example.com/notion/image.png\" alt=\"\" /></p>\n<p>Check out <a href=\"https://academy.notion.com/?utm_campaign=gsqw\">Notion Academy</a> to level up.</p>\n")
                ->has('procedureSteps', 2)
                ->has('glossaryTerms', 1)
            );
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
                ->where('procedureSteps', [])
                ->where('glossaryTerms', [])
            );
    }
}
