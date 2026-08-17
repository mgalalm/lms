<?php

namespace Tests\Feature\Filament;

use App\Enums\SessionType;
use App\Filament\Resources\StudentProfiles\Pages\EditStudentProfile;
use App\Filament\Resources\StudentProfiles\Pages\ListStudentProfiles;
use App\Models\ClassSession;
use App\Models\SessionPackagePurchase;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StudentProfileGdprTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_gdpr_erase_scrubs_personal_data_but_keeps_financial_and_session_records(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create([
            'name' => 'Jamie Student',
            'email' => 'jamie@example.com',
        ]);
        $student->studentProfile->update([
            'parent_name' => 'Pat Guardian',
            'parent_email' => 'parent@example.com',
            'parent_phone' => '+353123456789',
        ]);

        $purchase = SessionPackagePurchase::factory()->paid()->for($student)->create(['session_count' => 10, 'price_cents' => 15000]);
        $session = ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['type' => SessionType::Physical]);
        $student->sessionCreditLedger()->create(['credits' => 10, 'reason' => 'purchase']);

        $student->gdprErase();
        $student->refresh();

        $this->assertSame('Deleted User', $student->name);
        $this->assertSame("deleted-user-{$student->id}@erased.invalid", $student->email);
        $this->assertNotNull($student->gdpr_erased_at);
        $this->assertFalse($student->hasRole('student'));

        $studentProfile = $student->studentProfile->fresh();
        $this->assertNull($studentProfile->parent_name);
        $this->assertNull($studentProfile->parent_email);
        $this->assertNull($studentProfile->parent_phone);

        // Financial and session records survive, untouched.
        $this->assertDatabaseHas('session_package_purchases', [
            'id' => $purchase->id,
            'user_id' => $student->id,
            'session_count' => 10,
            'price_cents' => 15000,
        ]);
        $this->assertDatabaseHas('class_sessions', [
            'id' => $session->id,
            'student_id' => $student->id,
        ]);
        // 10 purchased, minus 1 the ClassSessionObserver already debited for
        // the session created above — unrelated to erasure, just confirms
        // the ledger itself wasn't touched by gdprErase().
        $this->assertSame(9, $student->sessionCreditsBalance());
    }

    public function test_gdpr_erase_is_idempotent(): void
    {
        $student = User::factory()->student()->create();

        $student->gdprErase();
        $erasedAt = $student->fresh()->gdpr_erased_at;

        $student->refresh()->gdprErase();

        $this->assertTrue($erasedAt->equalTo($student->fresh()->gdpr_erased_at));
    }

    public function test_an_erased_student_cannot_log_in_with_their_old_credentials(): void
    {
        $student = User::factory()->student()->create([
            'email' => 'jamie@example.com',
            'password' => Hash::make('password'),
        ]);

        $student->gdprErase();

        $this->post('/login', ['email' => 'jamie@example.com', 'password' => 'password'])
            ->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_admin_can_erase_a_student_from_the_list_with_the_correct_confirmation_code(): void
    {
        $admin = $this->admin();
        $student = User::factory()->student()->create();

        $this->actingAs($admin);

        Livewire::test(ListStudentProfiles::class)
            ->callAction(TestAction::make('gdprErase')->table($student->studentProfile), [
                'confirm_code' => $student->studentProfile->code,
            ])
            ->assertHasNoFormErrors();

        $this->assertNotNull($student->fresh()->gdpr_erased_at);
    }

    public function test_erase_is_rejected_with_the_wrong_confirmation_code(): void
    {
        $admin = $this->admin();
        $student = User::factory()->student()->create();

        $this->actingAs($admin);

        Livewire::test(ListStudentProfiles::class)
            ->callAction(TestAction::make('gdprErase')->table($student->studentProfile), [
                'confirm_code' => 'wrong-code',
            ])
            ->assertHasFormErrors(['confirm_code']);

        $this->assertNull($student->fresh()->gdpr_erased_at);
    }

    public function test_admin_can_erase_a_student_from_the_edit_page(): void
    {
        $admin = $this->admin();
        $student = User::factory()->student()->create();

        $this->actingAs($admin);

        Livewire::test(EditStudentProfile::class, ['record' => $student->studentProfile->getRouteKey()])
            ->callAction('gdprErase', ['confirm_code' => $student->studentProfile->code])
            ->assertHasNoFormErrors();

        $this->assertNotNull($student->fresh()->gdpr_erased_at);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
