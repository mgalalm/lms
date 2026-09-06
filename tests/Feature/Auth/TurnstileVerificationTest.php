<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        config(['services.turnstile.secret_key' => 'test-secret']);
    }

    public function test_registration_succeeds_when_turnstile_verifies_the_token(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $response = $this->post('/register', [...$this->validPayload(), 'turnstile_token' => 'valid-token']);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        Http::assertSent(fn ($request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'valid-token');
    }

    public function test_registration_fails_when_turnstile_rejects_the_token(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false]),
        ]);

        $response = $this->post('/register', [...$this->validPayload(), 'turnstile_token' => 'bad-token']);

        $response->assertInvalid(['turnstile_token']);
        $this->assertGuest();
    }

    public function test_registration_fails_when_no_token_is_submitted(): void
    {
        $response = $this->post('/register', [...$this->validPayload(), 'turnstile_token' => '']);

        $response->assertInvalid(['turnstile_token']);
        $this->assertGuest();
    }

    public function test_registration_fails_when_the_verification_request_errors(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(null, 500),
        ]);

        $response = $this->post('/register', [...$this->validPayload(), 'turnstile_token' => 'valid-token']);

        $response->assertInvalid(['turnstile_token']);
        $this->assertGuest();
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
            'consent' => true,
        ];
    }
}
