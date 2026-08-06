<?php

namespace Database\Seeders;

use App\Enums\ChannelType;
use App\Enums\MessageType;
use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Enums\UserRole;
use App\Models\Channel;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed login-ready teachers, students, and an admin, with sessions and
     * Community channels between them, so every dashboard has something to
     * show in local dev.
     */
    public function run(): void
    {
        $teacher = User::factory()->teacher()->create([
            'name' => 'Demo Teacher',
            'email' => 'teacher@example.com',
        ]);

        $teacherTwo = User::factory()->teacher()->create([
            'name' => 'Demo Teacher Two',
            'email' => 'teacher2@example.com',
        ]);

        $student = User::factory()->student()->create([
            'name' => 'Demo Student',
            'email' => 'student@example.com',
        ]);

        $studentTwo = User::factory()->student()->create([
            'name' => 'Demo Student Two',
            'email' => 'student2@example.com',
        ]);

        User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
        ])->assignRole(UserRole::Admin->value);

        $this->seedSessions($teacher, $teacherTwo, $student, $studentTwo);
        $this->seedCommunityChannels($teacher, $teacherTwo, $student, $studentTwo);
    }

    /**
     * Seed a spread of sessions across both teachers and both students, so
     * the Next Session widget and Sessions Schedule tabs all have data.
     */
    private function seedSessions(User $teacher, User $teacherTwo, User $student, User $studentTwo): void
    {
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

        // A second student on the first teacher's schedule.
        ClassSession::factory()->for($teacher, 'teacher')->for($studentTwo, 'student')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
            'zoom_meeting_id' => 'demo-meeting-4',
            'zoom_join_url' => 'https://zoom.us/j/demo4',
            'zoom_start_url' => 'https://zoom.us/s/demo4',
        ]);

        // A second teacher on the first student's schedule.
        ClassSession::factory()->for($teacherTwo, 'teacher')->for($student, 'student')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addHours(5),
            'zoom_meeting_id' => 'demo-meeting-5',
            'zoom_join_url' => 'https://zoom.us/j/demo5',
            'zoom_start_url' => 'https://zoom.us/s/demo5',
        ]);

        // Gives the second teacher a Past entry too.
        ClassSession::factory()->for($teacherTwo, 'teacher')->for($studentTwo, 'student')->completed()->create([
            'type' => SessionType::Online,
            'zoom_meeting_id' => 'demo-meeting-6',
        ]);
    }

    /**
     * Seed direct channels per teacher/student pair, plus group channels
     * with multiple members, so Community has something to show.
     */
    private function seedCommunityChannels(User $teacher, User $teacherTwo, User $student, User $studentTwo): void
    {
        $direct = $this->directChannel($teacher, $student);

        $direct->messages()->create([
            'user_id' => $teacher->id,
            'type' => MessageType::TaskCard,
            'meta' => [
                'title' => 'Reminder: for next time',
                'accent_color' => '#2563eb',
                'icon' => 'clipboard-list',
            ],
            'body' => '<p>Please review the <strong>Low Fidelity Wireframe</strong> lesson before our next session.</p>',
        ]);

        $direct->messages()->create([
            'user_id' => $student->id,
            'type' => MessageType::Text,
            'body' => '<p>Got it, thank you!</p>',
        ]);

        $this->directChannel($teacher, $studentTwo)->messages()->create([
            'user_id' => $teacher->id,
            'type' => MessageType::Text,
            'body' => '<p>Great work in today\'s session!</p>',
        ]);

        $this->directChannel($teacherTwo, $student)->messages()->create([
            'user_id' => $student->id,
            'type' => MessageType::Text,
            'body' => '<p>Hi! Looking forward to our first session.</p>',
        ]);

        $announcements = Channel::factory()->create([
            'type' => ChannelType::Group,
            'name' => 'Announcements',
        ]);
        $announcements->users()->attach([$teacher->id, $teacherTwo->id, $student->id, $studentTwo->id]);

        $announcements->messages()->create([
            'user_id' => $teacher->id,
            'type' => MessageType::Text,
            'body' => '<p>Welcome to the new term! Looking forward to a great year.</p>',
        ]);

        $moduleChannel = Channel::factory()->create([
            'type' => ChannelType::Group,
            'name' => 'Grade 5-6 - M2: Chatbot Development',
        ]);
        $moduleChannel->users()->attach([$teacher->id, $student->id, $studentTwo->id]);

        $moduleChannel->messages()->create([
            'user_id' => $teacher->id,
            'type' => MessageType::Text,
            'body' => '<p>This week we\'re starting the chatbot data encryption module — see you all in class!</p>',
        ]);
    }

    private function directChannel(User $a, User $b): Channel
    {
        $channel = Channel::factory()->create(['type' => ChannelType::Direct]);
        $channel->users()->attach([$a->id, $b->id]);

        return $channel;
    }
}
