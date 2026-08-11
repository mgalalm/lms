<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_teacher_does_not_see_student_only_topics(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('help.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('help/Index')
            ->where('categories.Teaching.0.title', 'Managing your Sessions Schedule')
            ->missing('categories.Rewards'));
    }

    public function test_a_student_does_not_see_teacher_only_topics(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('help.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('help/Index')
            ->where('categories.Rewards.0.title', 'Points, badges, and the leaderboard')
            ->missing('categories.Teaching'));
    }

    public function test_visiting_a_topic_shows_its_rendered_content(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('help.show', 'joining-a-session'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('help/Index')
            ->where('selected.slug', 'joining-a-session')
            ->has('selected.body_html'));
    }

    public function test_visiting_a_topic_not_visible_to_your_role_404s(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('help.show', 'payroll'))
            ->assertNotFound();
    }

    public function test_search_narrows_the_topic_list(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('help.index', ['search' => 'certification']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('help/Index')
            ->where('categories.Teaching.0.title', 'The LMS and certification gate')
            ->has('categories.Teaching', 1));
    }
}
