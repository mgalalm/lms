<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\StudentProfiles\Pages\EditStudentProfile;
use App\Filament\Resources\TeacherProfiles\Pages\EditTeacherProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileEditFormHydrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_student_profile_edit_form_shows_the_students_name_and_email(): void
    {
        $admin = $this->admin();
        $student = User::factory()->student()->create([
            'name' => 'Jamie Student',
            'email' => 'jamie@example.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditStudentProfile::class, ['record' => $student->studentProfile->getRouteKey()])
            ->assertFormSet([
                'user' => [
                    'name' => 'Jamie Student',
                    'email' => 'jamie@example.com',
                ],
            ]);
    }

    public function test_teacher_profile_edit_form_shows_the_teachers_name_and_email(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->teacher()->create([
            'name' => 'Taylor Teacher',
            'email' => 'taylor@example.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditTeacherProfile::class, ['record' => $teacher->teacherProfile->getRouteKey()])
            ->assertFormSet([
                'user' => [
                    'name' => 'Taylor Teacher',
                    'email' => 'taylor@example.com',
                ],
            ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
