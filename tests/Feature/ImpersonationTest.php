<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_an_admin_can_impersonate_a_student(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->student()->create();

        $this->actingAs($admin)
            ->get(route('impersonate.start', $student))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student);
        $this->assertSame($admin->id, session('impersonator_id'));

        $activity = Activity::latest('id')->first();
        $this->assertSame('Started impersonating', $activity->description);
        $this->assertTrue($activity->causer->is($admin));
        $this->assertTrue($activity->subject->is($student));
    }

    public function test_an_admin_can_impersonate_a_teacher(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(route('impersonate.start', $teacher))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_an_admin_cannot_impersonate_another_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('impersonate.start', $otherAdmin))
            ->assertForbidden();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_non_admin_cannot_start_impersonation(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($teacher)
            ->get(route('impersonate.start', $student))
            ->assertForbidden();

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_an_impersonated_user_can_return_to_the_admin_session(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->student()->create();

        $this->actingAs($admin)->get(route('impersonate.start', $student));

        $this->post(route('impersonate.stop'))
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));

        $activity = Activity::latest('id')->first();
        $this->assertSame('Stopped impersonating', $activity->description);
        $this->assertTrue($activity->causer->is($admin));
        $this->assertTrue($activity->subject->is($student));
    }

    public function test_stopping_impersonation_without_an_active_session_is_not_found(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('impersonate.stop'))
            ->assertNotFound();
    }
}
