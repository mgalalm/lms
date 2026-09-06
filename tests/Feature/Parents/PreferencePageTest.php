<?php

namespace Tests\Feature\Parents;

use App\Models\ParentContact;
use App\Models\User;
use App\Notifications\ParentPreferenceLinkRequested;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PreferencePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_validly_signed_link_within_7_days_renders_the_page(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create();

        $link = URL::temporarySignedRoute('parents.preferences.show', now()->addDays(7), ['parentContact' => $contact->id]);

        $this->get($link)->assertOk();
    }

    public function test_an_expired_signature_is_rejected(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create();

        $link = URL::temporarySignedRoute('parents.preferences.show', now()->subMinute(), ['parentContact' => $contact->id]);

        $this->get($link)->assertForbidden();
    }

    public function test_a_tampered_signature_is_rejected(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create();

        $link = URL::temporarySignedRoute('parents.preferences.show', now()->addDays(7), ['parentContact' => $contact->id]);

        $this->get($link.'&tampered=1')->assertForbidden();
    }

    /**
     * The safeguarding control: even with an otherwise-valid signed link,
     * a logged-in student may not view/edit their own parent's
     * preferences. Enforced by ParentContactPolicy::manage, not the UI.
     */
    public function test_a_logged_in_student_cannot_use_a_valid_signed_link(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create();

        $link = URL::temporarySignedRoute('parents.preferences.show', now()->addDays(7), ['parentContact' => $contact->id]);

        $this->actingAs($student)->get($link)->assertForbidden();
    }

    /**
     * The policy only restricts the Student role — a teacher or admin
     * following a link isn't the safeguarding concern the task calls out,
     * so this is deliberately allowed rather than blocked.
     */
    public function test_a_teacher_can_use_a_valid_signed_link(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')->create();
        $teacher = User::factory()->teacher()->create();

        $link = URL::temporarySignedRoute('parents.preferences.show', now()->addDays(7), ['parentContact' => $contact->id]);

        $this->actingAs($teacher)->get($link)->assertOk();
    }

    public function test_requesting_a_link_for_a_verified_contact_point_sends_one(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        ParentContact::factory()->for($student, 'student')->emailVerified()->create(['email' => 'parent@example.com']);

        $this->post(route('parents.preferences.request.store'), ['contact' => 'parent@example.com'])
            ->assertSessionHas('success');

        Notification::assertSentOnDemand(
            ParentPreferenceLinkRequested::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'parent@example.com'
        );
    }

    public function test_requesting_a_link_for_an_unverified_contact_point_sends_nothing(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        ParentContact::factory()->for($student, 'student')->create(['email' => 'parent@example.com', 'email_verified_at' => null]);

        $this->post(route('parents.preferences.request.store'), ['contact' => 'parent@example.com'])
            ->assertSessionHas('success');

        Notification::assertSentOnDemandTimes(ParentPreferenceLinkRequested::class, 0);
    }

    public function test_requesting_a_link_for_an_unknown_contact_gives_the_same_generic_response(): void
    {
        $response = $this->post(route('parents.preferences.request.store'), ['contact' => 'nobody@example.com']);

        $response->assertSessionHas('success');
    }

    public function test_requesting_a_link_is_rate_limited(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('parents.preferences.request.store'), ['contact' => 'nobody@example.com'])
                ->assertSessionHas('success');
        }

        $this->post(route('parents.preferences.request.store'), ['contact' => 'nobody@example.com'])
            ->assertStatus(429);
    }
}
