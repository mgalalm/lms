<?php

namespace Tests\Unit;

use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_timezone_falls_back_to_the_default_when_unset(): void
    {
        $profile = StudentProfile::factory()->create(['timezone' => null]);

        $this->assertSame(StudentProfile::DEFAULT_TIMEZONE, $profile->resolvedTimezone());
    }

    public function test_resolved_timezone_returns_the_students_own_timezone_when_set(): void
    {
        $profile = StudentProfile::factory()->create(['timezone' => 'Africa/Cairo']);

        $this->assertSame('Africa/Cairo', $profile->resolvedTimezone());
    }

    public function test_parent_guardian_fields_are_encrypted_at_rest(): void
    {
        $profile = StudentProfile::factory()->create([
            'parent_name' => 'Pat Guardian',
            'parent_email' => 'parent@example.com',
            'parent_phone' => '+353871234567',
        ]);

        $raw = DB::table('student_profiles')->where('id', $profile->id)->first();

        $this->assertNotSame('Pat Guardian', $raw->parent_name);
        $this->assertNotSame('parent@example.com', $raw->parent_email);
        $this->assertNotSame('+353871234567', $raw->parent_phone);

        $fresh = StudentProfile::find($profile->id);

        $this->assertSame('Pat Guardian', $fresh->parent_name);
        $this->assertSame('parent@example.com', $fresh->parent_email);
        $this->assertSame('+353871234567', $fresh->parent_phone);
    }

    public function test_null_parent_guardian_fields_stay_null(): void
    {
        $profile = StudentProfile::factory()->create([
            'parent_name' => null,
            'parent_email' => null,
            'parent_phone' => null,
        ]);

        $raw = DB::table('student_profiles')->where('id', $profile->id)->first();

        $this->assertNull($raw->parent_name);
        $this->assertNull($raw->parent_email);
        $this->assertNull($raw->parent_phone);
    }
}
