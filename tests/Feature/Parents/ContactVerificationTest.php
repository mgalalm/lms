<?php

namespace Tests\Feature\Parents;

use App\Enums\ParentContactChannel;
use App\Enums\ParentContactVerificationResult;
use App\Exceptions\Parents\TooManyVerificationSendsException;
use App\Models\ParentContact;
use App\Models\User;
use App\Notifications\ParentContactVerificationCode;
use App\Services\Parents\ContactVerificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_correct_code_verifies_the_contact_point(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create(['email' => 'parent@example.com']);

        $sentCode = $this->capturedCode();

        $result = app(ContactVerificationService::class)->verifyCode($contact, ParentContactChannel::Email, $sentCode);

        $this->assertSame(ParentContactVerificationResult::Verified, $result);
        $this->assertNotNull($contact->fresh()->email_verified_at);
    }

    public function test_an_incorrect_code_is_rejected(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create(['email' => 'parent@example.com']);

        $result = app(ContactVerificationService::class)->verifyCode($contact, ParentContactChannel::Email, '000000');

        $this->assertSame(ParentContactVerificationResult::Incorrect, $result);
        $this->assertNull($contact->fresh()->email_verified_at);
    }

    public function test_a_code_expires_after_15_minutes(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create(['email' => 'parent@example.com']);
        $sentCode = $this->capturedCode();

        $this->travel(16)->minutes();

        $result = app(ContactVerificationService::class)->verifyCode($contact, ParentContactChannel::Email, $sentCode);

        $this->assertSame(ParentContactVerificationResult::Expired, $result);
    }

    public function test_sending_a_code_is_rate_limited_to_3_per_hour(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        // Creating the contact already sends one code (via the observer).
        $contact = ParentContact::factory()->for($student, 'student')->create(['email' => 'parent@example.com']);

        $service = app(ContactVerificationService::class);

        $service->sendCode($contact, ParentContactChannel::Email);
        $service->sendCode($contact, ParentContactChannel::Email);

        $this->expectException(TooManyVerificationSendsException::class);
        $service->sendCode($contact, ParentContactChannel::Email);
    }

    public function test_changing_the_email_resets_verification_and_sends_a_fresh_code(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->emailVerified()->create(['email' => 'old@example.com']);

        $contact->update(['email' => 'new@example.com']);

        $this->assertNull($contact->fresh()->email_verified_at);
        Notification::assertSentOnDemand(
            ParentContactVerificationCode::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'new@example.com'
        );
    }

    private function capturedCode(): string
    {
        $code = null;

        Notification::assertSentOnDemand(
            ParentContactVerificationCode::class,
            function (ParentContactVerificationCode $notification) use (&$code) {
                $code = $notification->code;

                return true;
            }
        );

        return $code;
    }
}
