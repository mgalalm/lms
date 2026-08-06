<?php

namespace Tests\Feature;

use App\Models\PayrollEntry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_can_view_their_payroll_page(): void
    {
        $teacher = User::factory()->teacher()->create();
        PayrollEntry::factory()->create(['teacher_profile_id' => $teacher->teacherProfile->id]);

        $this->actingAs($teacher)
            ->get(route('payroll.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Payroll')
                ->count('entries', 1)
            );
    }

    public function test_students_cannot_view_payroll(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('payroll.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_payroll(): void
    {
        $this->get(route('payroll.index'))->assertRedirect('/login');
    }

    public function test_net_amount_includes_bonus_and_deduction(): void
    {
        $teacher = User::factory()->teacher()->create();
        $entry = PayrollEntry::factory()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'basic_salary' => 5000,
        ]);
        $entry->adjustments()->create(['type' => 'bonus', 'amount' => 300, 'reason' => 'Bonus']);
        $entry->adjustments()->create(['type' => 'deduction', 'amount' => 100, 'reason' => 'Deduction']);

        $this->assertSame(5200.0, $entry->netAmount());

        $this->actingAs($teacher)
            ->get(route('payroll.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Payroll')
                ->where('entries.0.net_amount', 5200)
                ->where('entries.0.bonus_total', 300)
                ->where('entries.0.deduction_total', 100)
            );
    }

    public function test_payroll_only_shows_the_authenticated_teachers_entries(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        PayrollEntry::factory()->create(['teacher_profile_id' => $teacher->teacherProfile->id]);
        PayrollEntry::factory()->create(['teacher_profile_id' => $otherTeacher->teacherProfile->id]);

        $this->actingAs($teacher)
            ->get(route('payroll.index'))
            ->assertInertia(fn (Assert $page) => $page->count('entries', 1));
    }
}
