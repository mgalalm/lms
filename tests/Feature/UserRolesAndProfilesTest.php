<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRolesAndProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_factory_state_assigns_role_and_creates_profile(): void
    {
        $user = User::factory()->teacher()->create();

        $this->assertTrue($user->hasRole(UserRole::Teacher->value));
        $this->assertFalse($user->hasRole(UserRole::Student->value));
        $this->assertInstanceOf(TeacherProfile::class, $user->teacherProfile);
        $this->assertSame($user->id, $user->teacherProfile->user_id);
    }

    public function test_student_factory_state_assigns_role_and_creates_profile(): void
    {
        $user = User::factory()->student()->create();

        $this->assertTrue($user->hasRole(UserRole::Student->value));
        $this->assertFalse($user->hasRole(UserRole::Teacher->value));
        $this->assertInstanceOf(StudentProfile::class, $user->studentProfile);
        $this->assertSame($user->id, $user->studentProfile->user_id);
    }

    public function test_admin_factory_state_assigns_role_without_a_profile(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->hasRole(UserRole::Admin->value));
        $this->assertNull($user->teacherProfile);
        $this->assertNull($user->studentProfile);
    }

    public function test_role_seeder_creates_the_three_core_roles(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role->value]);
        }
    }
}
