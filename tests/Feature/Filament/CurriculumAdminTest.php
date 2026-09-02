<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\RelationManagers\ModulesRelationManager;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\RelationManagers\LessonsRelationManager;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurriculumAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_non_admins_cannot_access_the_admin_panel(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/admin')->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admins_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_admin_can_list_courses(): void
    {
        $admin = $this->admin();
        Course::factory()->sequence(
            ['grade_band' => 'Grade 5 - 6'],
            ['grade_band' => 'Grade 7 - 8'],
            ['grade_band' => 'Grade 9 - 10'],
        )->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListCourses::class)->assertOk();
    }

    public function test_admin_cannot_create_a_course(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->get(route('filament.admin.resources.courses.create'))->assertForbidden();
    }

    public function test_admin_cannot_delete_a_course(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();
        $this->actingAs($admin);

        Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertActionDoesNotExist('delete');
    }

    public function test_admin_can_add_a_module_to_a_course_via_the_relation_manager(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ModulesRelationManager::class, [
            'ownerRecord' => $course,
            'pageClass' => EditCourse::class,
        ])
            ->assertOk()
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'title' => 'M2: Chatbot Development',
                'order' => 1,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('modules', [
            'course_id' => $course->id,
            'title' => 'M2: Chatbot Development',
        ]);
    }

    public function test_admin_can_add_a_lesson_to_a_module_via_the_relation_manager(): void
    {
        $admin = $this->admin();
        $module = Module::factory()->create();
        $this->actingAs($admin);

        Livewire::test(LessonsRelationManager::class, [
            'ownerRecord' => $module,
            'pageClass' => EditModule::class,
        ])
            ->assertOk()
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'title' => 'L11: Tkinter in Python',
                'order' => 11,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('lessons', [
            'module_id' => $module->id,
            'title' => 'L11: Tkinter in Python',
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
