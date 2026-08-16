<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
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

    public function test_registering_stores_the_parent_email(): void
    {
        $this->post('/register', [...$this->validPayload(), 'parent_email' => 'parent@example.com']);

        $studentProfile = User::where('email', 'test@example.com')->firstOrFail()->studentProfile;

        $this->assertSame('parent@example.com', $studentProfile->parent_email);
    }

    public function test_registering_records_when_consent_was_given()
    {
        $this->post('/register', $this->validPayload());

        $studentProfile = User::where('email', 'test@example.com')->firstOrFail()->studentProfile;

        $this->assertNotNull($studentProfile->consent_given_at);
        $this->assertTrue($studentProfile->consent_given_at->diffInSeconds(now()) < 5);
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
            'parent_email' => 'test@example.com',
            'consent' => true,
        ];
    }
}
