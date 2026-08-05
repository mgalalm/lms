<?php

namespace Database\Seeders;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed a login-ready teacher and student with a few sessions between
     * them, so the dashboards have something to show in local dev.
     */
    public function run(): void
    {
        $teacher = User::factory()->teacher()->create([
            'name' => 'Demo Teacher',
            'email' => 'teacher@example.com',
        ]);

        $student = User::factory()->student()->create([
            'name' => 'Demo Student',
            'email' => 'student@example.com',
        ]);

        // Soonest upcoming session — this is what the Next Session widget shows.
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addHours(3),
            'zoom_meeting_id' => 'demo-meeting-1',
            'zoom_join_url' => 'https://zoom.us/j/demo1',
            'zoom_start_url' => 'https://zoom.us/s/demo1',
        ]);

        // Later this week, for the Upcoming tab.
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addDays(2),
            'zoom_meeting_id' => 'demo-meeting-2',
            'zoom_join_url' => 'https://zoom.us/j/demo2',
            'zoom_start_url' => 'https://zoom.us/s/demo2',
        ]);

        // Already happened, for the Past tab.
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->completed()->create([
            'type' => SessionType::Online,
            'zoom_meeting_id' => 'demo-meeting-3',
        ]);
    }
}
