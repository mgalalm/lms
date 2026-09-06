<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use App\Notifications\StudentRegistered;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', $this->validPayload());

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_requires_consent()
    {
        $response = $this->post('/register', [...$this->validPayload(), 'consent' => null]);

        $response->assertInvalid(['consent']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_rejects_an_unchecked_consent_box()
    {
        $response = $this->post('/register', [...$this->validPayload(), 'consent' => false]);

        $response->assertInvalid(['consent']);
        $this->assertGuest();
    }

    public function test_registration_requires_a_parent_email()
    {
        $response = $this->post('/register', [...$this->validPayload(), 'parent_email' => null]);

        $response->assertInvalid(['parent_email']);
        $this->assertGuest();
    }

    public function test_registration_rejects_an_invalid_parent_email()
    {
        $response = $this->post('/register', [...$this->validPayload(), 'parent_email' => 'not-an-email']);

        $response->assertInvalid(['parent_email']);
        $this->assertGuest();
    }

    public function test_registration_requires_a_parent_name()
    {
        $response = $this->post('/register', [...$this->validPayload(), 'parent_name' => null]);

        $response->assertInvalid(['parent_name']);
        $this->assertGuest();
    }

    public function test_registration_does_not_require_a_parent_phone()
    {
        $response = $this->post('/register', [...$this->validPayload(), 'parent_phone' => null]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_registering_stores_the_parent_contact(): void
    {
        $this->post('/register', [
            ...$this->validPayload(),
            'parent_name' => 'Pat Guardian',
            'parent_email' => 'parent@example.com',
            'parent_phone' => '+353871234567',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame('Pat Guardian', $user->parentContact->name);
        $this->assertSame('parent@example.com', $user->parentContact->email);
        $this->assertSame('+353871234567', $user->parentContact->phone);
        // Legacy student_profiles columns are gone entirely — parent data
        // lives only on parent_contacts now.
        $this->assertArrayNotHasKey('parent_email', $user->studentProfile->getAttributes());
    }

    public function test_registering_records_when_consent_was_given()
    {
        $this->post('/register', $this->validPayload());

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertNotNull($user->parentContact->consent_given_at);
        $this->assertTrue($user->parentContact->consent_given_at->diffInSeconds(now()) < 5);
    }

    public function test_registering_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', $this->validPayload());

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, QueuedVerifyEmail::class);
    }

    public function test_new_users_are_registered_as_students_with_a_profile(): void
    {
        $this->post('/register', $this->validPayload());

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole(UserRole::Student->value));
        $this->assertNotNull($user->studentProfile);
        $this->assertNotNull($user->studentProfile->code);
    }

    public function test_registering_notifies_every_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole(UserRole::Admin->value);
        $teacher = User::factory()->teacher()->create();

        $this->post('/register', $this->validPayload());

        Notification::assertSentTo([$admin, $otherAdmin], StudentRegistered::class);
        Notification::assertNotSentTo($teacher, StudentRegistered::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'parent_name' => 'Test Parent',
            'parent_email' => 'test@example.com',
            'parent_phone' => null,
            'consent' => true,
        ];
    }
}
