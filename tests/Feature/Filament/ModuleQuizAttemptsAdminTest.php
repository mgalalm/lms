<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\RelationManagers\QuizAttemptsRelationManager;
use App\Models\Module;
use App\Models\TeacherModuleQuizAttempt;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleQuizAttemptsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_an_admin_can_reset_a_teachers_exhausted_module_quiz_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();

        $attempts = TeacherModuleQuizAttempt::factory()
            ->count(3)
            ->for($teacher->teacherProfile)
            ->for($module)
            ->create();

        $this->assertSame(0, $teacher->teacherProfile->attemptsRemainingFor($module));

        $this->actingAs($admin);

        Livewire::test(QuizAttemptsRelationManager::class, [
            'ownerRecord' => $module,
            'pageClass' => EditModule::class,
        ])
            ->callTableAction('resetAttempts', $attempts->first())
            ->assertNotified();

        // All three voided, so the teacher gets a full allowance back —
        // and the history is kept rather than deleted.
        $this->assertSame(3, $teacher->teacherProfile->attemptsRemainingFor($module));
        $this->assertDatabaseCount('teacher_module_quiz_attempts', 3);
        $this->assertSame(0, TeacherModuleQuizAttempt::whereNull('voided_at')->count());
    }
}
