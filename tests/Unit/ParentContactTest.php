<?php

namespace Tests\Unit;

use App\Enums\ParentNotificationChannel;
use App\Enums\ParentNotificationType;
use App\Models\ParentContact;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ParentContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_name_email_and_phone_are_encrypted_at_rest(): void
    {
        Notification::fake();

        $contact = ParentContact::factory()->withPhone('+353871234567')->create([
            'name' => 'Pat Guardian',
            'email' => 'parent@example.com',
        ]);

        $raw = DB::table('parent_contacts')->where('id', $contact->id)->first();

        $this->assertNotSame('Pat Guardian', $raw->name);
        $this->assertNotSame('parent@example.com', $raw->email);
        $this->assertNotSame('+353871234567', $raw->phone);

        $fresh = ParentContact::find($contact->id);

        $this->assertSame('Pat Guardian', $fresh->name);
        $this->assertSame('parent@example.com', $fresh->email);
        $this->assertSame('+353871234567', $fresh->phone);
    }

    public function test_email_hash_and_phone_hash_are_not_the_plaintext_but_are_deterministic(): void
    {
        Notification::fake();

        $contact = ParentContact::factory()->withPhone('+353871234567')->create(['email' => 'parent@example.com']);
        $raw = DB::table('parent_contacts')->where('id', $contact->id)->first();

        $this->assertNotSame('parent@example.com', $raw->email_hash);
        $this->assertNotSame('+353871234567', $raw->phone_hash);
        $this->assertSame(hash_hmac('sha256', 'parent@example.com', config('app.key')), $raw->email_hash);
        $this->assertSame(hash_hmac('sha256', '+353871234567', config('app.key')), $raw->phone_hash);
    }

    public function test_seeding_default_preferences_never_defaults_marketing_to_on(): void
    {
        Notification::fake();

        $contact = ParentContact::factory()->create();

        $this->assertSame(
            ParentNotificationChannel::None,
            $contact->preferenceFor(ParentNotificationType::Marketing)
        );
        $this->assertSame(
            ParentNotificationChannel::Sms,
            $contact->preferenceFor(ParentNotificationType::UrgentPing)
        );
    }
}
