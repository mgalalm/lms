<?php

namespace Tests\Unit;

use App\Models\TeacherProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_timezone_falls_back_to_the_default_when_unset(): void
    {
        $profile = TeacherProfile::factory()->create(['timezone' => null]);

        $this->assertSame(TeacherProfile::DEFAULT_TIMEZONE, $profile->resolvedTimezone());
    }

    public function test_resolved_timezone_returns_the_teachers_own_timezone_when_set(): void
    {
        $profile = TeacherProfile::factory()->create(['timezone' => 'Africa/Cairo']);

        $this->assertSame('Africa/Cairo', $profile->resolvedTimezone());
    }
}
