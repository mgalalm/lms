<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create(['name' => 'Jamie Student']);

        $raw = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame('Jamie Student', $raw->name);

        $fresh = User::find($user->id);

        $this->assertSame('Jamie Student', $fresh->name);
    }

    public function test_email_stays_plaintext_and_queryable(): void
    {
        User::factory()->create(['email' => 'lookup@example.com']);

        $this->assertTrue(User::where('email', 'lookup@example.com')->exists());
    }
}
