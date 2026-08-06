<?php

namespace Tests\Feature;

use App\Filament\Resources\TeacherRequests\Pages\ListTeacherRequests;
use App\Models\LeaveCredit;
use App\Models\TeacherRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_can_view_the_requests_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('requests.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Requests/Index')
                ->where('type', 'leave')
                ->has('leaveCredit')
            );
    }

    public function test_students_cannot_view_requests(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('requests.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_requests(): void
    {
        $this->get(route('requests.index'))->assertRedirect('/login');
    }

    public function test_teacher_can_submit_a_leave_request(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->post(route('requests.store'), [
                'type' => 'leave',
                'leave_type' => 'planned',
                'sub_reason' => 'Vacation',
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDay()->toDateString(),
                'notes' => 'Trip',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_requests', [
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'type' => 'leave',
            'status' => 'pending',
        ]);

        $request = TeacherRequest::first();
        $this->assertSame('Vacation', $request->payload['sub_reason']);
    }

    public function test_leave_request_requires_start_and_end_date(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->post(route('requests.store'), [
                'type' => 'leave',
                'leave_type' => 'planned',
                'sub_reason' => 'Vacation',
            ])
            ->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_teacher_can_submit_an_hr_request(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->post(route('requests.store'), [
                'type' => 'hr_request',
                'sub_reason' => 'Payslip question',
                'notes' => 'Details here.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_requests', [
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'type' => 'hr_request',
        ]);
    }

    public function test_history_can_be_filtered_by_type(): void
    {
        $teacher = User::factory()->teacher()->create();
        TeacherRequest::factory()->create(['teacher_profile_id' => $teacher->teacherProfile->id, 'type' => 'leave']);
        TeacherRequest::factory()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'type' => 'hr_request',
            'payload' => ['sub_reason' => 'Test', 'notes' => 'Test'],
        ]);

        $this->actingAs($teacher)
            ->get(route('requests.index', ['type' => 'hr_request']))
            ->assertInertia(fn (Assert $page) => $page->count('history.data', 1)
                ->where('history.data.0.type', 'hr_request')
            );
    }

    public function test_admin_can_approve_a_leave_request_and_leave_credit_is_debited(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->teacher()->create();
        $teacherRequest = TeacherRequest::factory()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'type' => 'leave',
            'payload' => [
                'leave_type' => 'planned',
                'sub_reason' => 'Vacation',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-02',
            ],
        ]);

        $this->actingAs($admin);

        Livewire::test(ListTeacherRequests::class)
            ->callAction(TestAction::make('approve')->table($teacherRequest));

        $this->assertSame('approved', $teacherRequest->fresh()->status->value);

        $credit = LeaveCredit::where('teacher_profile_id', $teacher->teacherProfile->id)->first();
        $this->assertNotNull($credit);
        $this->assertSame(2, $credit->monthly_used);
        $this->assertSame(2, $credit->annual_used);
    }
}
