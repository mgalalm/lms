<?php

namespace Database\Seeders;

use App\Enums\ChannelType;
use App\Enums\FlagSeverity;
use App\Enums\MessageType;
use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\Channel;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\InsightMetric;
use App\Models\LeaveCredit;
use App\Models\Lesson;
use App\Models\PayrollEntry;
use App\Models\PointsLedgerEntry;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\SessionReview;
use App\Models\TeacherCourseCompletion;
use App\Models\TeacherRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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

        [$lessonOne, $lessonTwo] = $this->seedCurriculumAndCertifications($teacher, $teacherTwo);
        $this->seedSessions($teacher, $teacherTwo, $student, $studentTwo, $lessonOne, $lessonTwo);
        $this->seedCommunityChannels($teacher, $teacherTwo, $student, $studentTwo);
        $this->seedInsightsData($teacher, $teacherTwo, $student, $studentTwo, $lessonOne, $lessonTwo);
        $this->seedGamification($teacher, $teacherTwo, $student, $studentTwo);
        $this->seedPayrollAndRequests($teacher, $teacherTwo);
    }

    /**
     * Seed two courses (each with a module, a couple of lessons, and a
     * certification quiz), give each demo teacher a mix of LMS progress —
     * one fully certified course and one still in progress — and certify
     * each for the course they'll be scheduled to teach. Certification is
     * what the gate in ClassSessionForm checks against.
     *
     * @return array{0: Lesson, 1: Lesson}
     */
    private function seedCurriculumAndCertifications(User $teacher, User $teacherTwo): array
    {
        $courseOne = Course::factory()->create([
            'title' => 'M1: Intro to Scratch',
            'grade_band' => 'Grade 3-4',
        ]);
        $moduleOne = $courseOne->modules()->create(['title' => 'Getting Started', 'order' => 1]);
        $lessonOne = Lesson::factory()->for($moduleOne, 'module')->create([
            'title' => 'L1: Sprites and the Stage',
            'order' => 1,
        ]);

        $courseTwo = Course::factory()->create([
            'title' => 'M2: Chatbot Development',
            'grade_band' => 'Grade 5-6',
        ]);
        $moduleTwo = $courseTwo->modules()->create(['title' => 'Building a Bot', 'order' => 1]);
        $lessonTwoA = Lesson::factory()->for($moduleTwo, 'module')->create([
            'title' => 'L1: Intents and Responses',
            'order' => 1,
        ]);
        $lessonTwoB = Lesson::factory()->for($moduleTwo, 'module')->create([
            'title' => 'L2: Training the Model',
            'order' => 2,
        ]);

        $quizOne = Quiz::factory()->create(['course_id' => $courseOne->id]);
        QuizQuestion::factory()->create([
            'quiz_id' => $quizOne->id,
            'question' => 'Which block category moves a sprite around the stage?',
            'options' => ['Motion', 'Sound', 'Looks', 'Control'],
            'correct_option' => 0,
            'order' => 1,
        ]);

        $quizTwo = Quiz::factory()->create(['course_id' => $courseTwo->id]);
        QuizQuestion::factory()->create([
            'quiz_id' => $quizTwo->id,
            'question' => 'What does an intent represent in a chatbot?',
            'options' => ["The user's goal for a message", "The bot's reply text", 'The database schema', 'The hosting provider'],
            'correct_option' => 0,
            'order' => 1,
        ]);
        QuizQuestion::factory()->create([
            'quiz_id' => $quizTwo->id,
            'question' => 'What do you feed a chatbot to teach it to recognize an intent?',
            'options' => ['Example phrases', 'CSS styles', 'Cron jobs', 'DNS records'],
            'correct_option' => 0,
            'order' => 2,
        ]);

        // Demo Teacher: certified for course one, still in progress on course two.
        $teacher->teacherProfile->lessonCompletions()->create(['lesson_id' => $lessonOne->id, 'completed_at' => now()->subMonths(6)]);
        $teacher->teacherProfile->lessonCompletions()->create(['lesson_id' => $lessonTwoA->id, 'completed_at' => now()->subDays(3)]);

        TeacherCourseCompletion::factory()->certified()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $courseOne->id,
        ]);

        // Demo Teacher Two: certified for course two, hasn't started course one.
        $teacherTwo->teacherProfile->lessonCompletions()->create(['lesson_id' => $lessonTwoA->id, 'completed_at' => now()->subMonths(4)]);
        $teacherTwo->teacherProfile->lessonCompletions()->create(['lesson_id' => $lessonTwoB->id, 'completed_at' => now()->subMonths(4)]);

        TeacherCourseCompletion::factory()->certified()->create([
            'teacher_profile_id' => $teacherTwo->teacherProfile->id,
            'course_id' => $courseTwo->id,
        ]);

        return [$lessonOne, $lessonTwoA];
    }

    /**
     * Seed a spread of sessions across both teachers and both students, so
     * the Next Session widget and Sessions Schedule tabs all have data.
     */
    private function seedSessions(User $teacher, User $teacherTwo, User $student, User $studentTwo, Lesson $lessonOne, Lesson $lessonTwo): void
    {
        // Soonest upcoming session — this is what the Next Session widget shows.
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lessonOne, 'lesson')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addHours(3),
            'zoom_meeting_id' => 'demo-meeting-1',
            'zoom_join_url' => 'https://zoom.us/j/demo1',
            'zoom_start_url' => 'https://zoom.us/s/demo1',
        ]);

        // Later this week, for the Upcoming tab.
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lessonOne, 'lesson')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addDays(2),
            'zoom_meeting_id' => 'demo-meeting-2',
            'zoom_join_url' => 'https://zoom.us/j/demo2',
            'zoom_start_url' => 'https://zoom.us/s/demo2',
        ]);

        // Already happened, for the Past tab.
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lessonOne, 'lesson')->completed()->create([
            'type' => SessionType::Online,
            'zoom_meeting_id' => 'demo-meeting-3',
        ]);

        // A second student on the first teacher's schedule.
        ClassSession::factory()->for($teacher, 'teacher')->for($studentTwo, 'student')->for($lessonOne, 'lesson')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
            'zoom_meeting_id' => 'demo-meeting-4',
            'zoom_join_url' => 'https://zoom.us/j/demo4',
            'zoom_start_url' => 'https://zoom.us/s/demo4',
        ]);

        // A second teacher on the first student's schedule.
        ClassSession::factory()->for($teacherTwo, 'teacher')->for($student, 'student')->for($lessonTwo, 'lesson')->create([
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => now()->addHours(5),
            'zoom_meeting_id' => 'demo-meeting-5',
            'zoom_join_url' => 'https://zoom.us/j/demo5',
            'zoom_start_url' => 'https://zoom.us/s/demo5',
        ]);

        // Gives the second teacher a Past entry too.
        ClassSession::factory()->for($teacherTwo, 'teacher')->for($studentTwo, 'student')->for($lessonTwo, 'lesson')->completed()->create([
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

    /**
     * Seed a spread of reviewed, completed sessions (with varied flags,
     * on-time/late joins, and performance scores) plus a monthly Insights
     * rollup for each demo teacher, so the Insights page has real data.
     */
    private function seedInsightsData(User $teacher, User $teacherTwo, User $student, User $studentTwo, Lesson $lessonOne, Lesson $lessonTwo): void
    {
        $this->seedReviewedSession($teacher, $student, $lessonOne, now()->subDays(2), onTime: true, flag: FlagSeverity::Green, scoreRange: [90, 98]);
        $this->seedReviewedSession($teacher, $studentTwo, $lessonOne, now()->subDays(5), onTime: true, flag: FlagSeverity::Green, scoreRange: [88, 95]);
        $this->seedReviewedSession($teacher, $student, $lessonOne, now()->subDays(9), onTime: false, flag: FlagSeverity::Yellow, scoreRange: [70, 80]);
        $this->seedReviewedSession($teacher, $studentTwo, $lessonOne, now()->subDays(14), onTime: true, flag: FlagSeverity::Green, scoreRange: [85, 92]);
        $this->seedReviewedSession($teacher, $student, $lessonOne, now()->subDays(20), onTime: false, flag: FlagSeverity::Red, scoreRange: [45, 60], notes: 'Joined 20 minutes late; student had to wait.');

        $this->seedReviewedSession($teacherTwo, $student, $lessonTwo, now()->subDays(3), onTime: true, flag: FlagSeverity::Green, scoreRange: [90, 97]);
        $this->seedReviewedSession($teacherTwo, $studentTwo, $lessonTwo, now()->subDays(11), onTime: true, flag: FlagSeverity::Green, scoreRange: [86, 93]);
        $this->seedReviewedSession($teacherTwo, $student, $lessonTwo, now()->subDays(18), onTime: false, flag: FlagSeverity::Yellow, scoreRange: [72, 82]);

        InsightMetric::factory()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'objections' => 1,
            'excuses' => 2,
            'study_level' => 7,
        ]);

        InsightMetric::factory()->create([
            'teacher_profile_id' => $teacherTwo->teacherProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'objections' => 0,
            'excuses' => 1,
            'study_level' => 8,
        ]);
    }

    /**
     * @param  array{0: int, 1: int}  $scoreRange
     */
    private function seedReviewedSession(
        User $teacher,
        User $student,
        Lesson $lesson,
        Carbon $scheduledAt,
        bool $onTime,
        FlagSeverity $flag,
        array $scoreRange,
        ?string $notes = null,
    ): void {
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->for($lesson, 'lesson')
            ->completed()
            ->create([
                'scheduled_at' => $scheduledAt,
                'teacher_joined_at' => $onTime ? $scheduledAt->copy()->addMinutes(2) : $scheduledAt->copy()->addMinutes(15),
            ]);

        SessionReview::factory()->create([
            'class_session_id' => $session->id,
            'flag_severity' => $flag,
            'feedback_score' => fake()->numberBetween(...$scoreRange),
            'attitude_score' => fake()->numberBetween(...$scoreRange),
            'preparation_score' => fake()->numberBetween(...$scoreRange),
            'curriculum_score' => fake()->numberBetween(...$scoreRange),
            'teaching_score' => fake()->numberBetween(...$scoreRange),
            'setup_score' => fake()->numberBetween(...$scoreRange),
            'notes' => $notes,
        ]);
    }

    /**
     * Seed a small badge catalog, award a couple to each student, and log
     * a spread of points-ledger entries so both the student Achievements
     * page and the teacher/student leaderboards have real data.
     */
    private function seedGamification(User $teacher, User $teacherTwo, User $student, User $studentTwo): void
    {
        $quizQuester = Badge::factory()->create([
            'name' => 'Quiz Quester',
            'description' => 'Passed a course certification quiz on the first try.',
            'icon' => 'trophy',
        ]);

        $pythonPioneer = Badge::factory()->create([
            'name' => 'Python Chatbot Pioneer',
            'description' => 'Completed the Chatbot Development module.',
            'icon' => 'rocket',
        ]);

        $timeLegend = Badge::factory()->create([
            'name' => 'Time Legend',
            'description' => 'Joined 10 sessions on time in a row.',
            'icon' => 'clock',
        ]);

        $student->badges()->attach([
            $quizQuester->id => ['awarded_at' => now()->subDays(10)],
            $timeLegend->id => ['awarded_at' => now()->subDays(2)],
        ]);

        $studentTwo->badges()->attach([
            $pythonPioneer->id => ['awarded_at' => now()->subDays(5)],
        ]);

        PointsLedgerEntry::factory()->create(['user_id' => $student->id, 'points' => 2890, 'reason' => 'Weekly learning streak bonus']);
        PointsLedgerEntry::factory()->create(['user_id' => $studentTwo->id, 'points' => 1650, 'reason' => 'Completed L1: Intents and Responses']);
        PointsLedgerEntry::factory()->create(['user_id' => $teacher->id, 'points' => 17550, 'reason' => 'Monthly performance bonus']);
        PointsLedgerEntry::factory()->create(['user_id' => $teacherTwo->id, 'points' => 9200, 'reason' => 'Monthly performance bonus']);
    }

    /**
     * Seed two months of payroll (with bonus/deduction line items) and a
     * spread of Requests (pending/approved/rejected) for each demo teacher,
     * so the Payroll and Requests pages have real data to show.
     */
    private function seedPayrollAndRequests(User $teacher, User $teacherTwo): void
    {
        foreach ([$teacher, $teacherTwo] as $index => $demoTeacher) {
            $basicSalary = 5000 + ($index * 1500);

            $lastMonth = PayrollEntry::factory()->paid()->create([
                'teacher_profile_id' => $demoTeacher->teacherProfile->id,
                'month' => now()->subMonthNoOverflow()->startOfMonth(),
                'sessions_count' => 42 + $index,
                'basic_salary' => $basicSalary,
            ]);
            $lastMonth->adjustments()->create(['type' => 'bonus', 'amount' => 300, 'reason' => 'On-time attendance bonus']);
            $lastMonth->adjustments()->create(['type' => 'deduction', 'amount' => 100, 'reason' => 'Late check-in']);

            PayrollEntry::factory()->create([
                'teacher_profile_id' => $demoTeacher->teacherProfile->id,
                'month' => now()->startOfMonth(),
                'sessions_count' => 18 + $index,
                'basic_salary' => $basicSalary,
            ]);

            LeaveCredit::factory()->create([
                'teacher_profile_id' => $demoTeacher->teacherProfile->id,
                'monthly_used' => 1,
                'annual_used' => 8,
            ]);
        }

        TeacherRequest::factory()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'type' => 'leave',
            'payload' => [
                'leave_type' => 'planned',
                'sub_reason' => 'Vacation',
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->addDay()->toDateString(),
                'notes' => 'Family trip.',
            ],
            'status' => 'pending',
        ]);

        TeacherRequest::factory()->approved()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'type' => 'leave',
            'payload' => [
                'leave_type' => 'sick',
                'sub_reason' => 'Sick',
                'start_date' => now()->subWeek()->toDateString(),
                'end_date' => now()->subWeek()->toDateString(),
                'notes' => 'Flu.',
            ],
            'reviewed_by' => null,
        ]);

        TeacherRequest::factory()->create([
            'teacher_profile_id' => $teacherTwo->teacherProfile->id,
            'type' => 'hr_request',
            'payload' => [
                'sub_reason' => 'Payslip question',
                'notes' => 'My last payslip shows a deduction I do not recognize.',
            ],
            'status' => 'pending',
        ]);
    }
}
