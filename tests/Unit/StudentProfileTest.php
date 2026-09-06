<?php

namespace Tests\Unit;

use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
