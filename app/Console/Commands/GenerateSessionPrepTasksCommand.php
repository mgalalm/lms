<?php

namespace App\Console\Commands;

use App\Enums\TeacherTaskType;
use App\Models\ClassSession;
use App\Models\TeacherTask;
use Illuminate\Console\Command;

/**
 * Creates a session_prep TeacherTask for every upcoming session that
 * doesn't already have one, due a configurable interval before the
 * session starts. Scheduled hourly (routes/console.php) rather than
 * daily like SyncNotionTutorGuidesCommand — a same-day-booked session
 * would otherwise go up to 23 hours without a prep reminder.
 */
class GenerateSessionPrepTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teaching:generate-session-prep-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate session_prep teacher tasks for upcoming sessions that don\'t already have one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $leadHours = (int) config('teaching.task_prep_lead_hours');

        $sessions = ClassSession::upcoming()
            ->whereDoesntHave('prepTask')
            ->with(['teacher', 'student', 'lesson'])
            ->get();

        foreach ($sessions as $session) {
            TeacherTask::create([
                'teacher_id' => $session->teacher_id,
                'title' => "Review Lesson – {$session->lesson->title}",
                'description' => "Prep for {$session->student->name}'s upcoming session on \"{$session->lesson->title}\".",
                'type' => TeacherTaskType::SessionPrep,
                'class_session_id' => $session->id,
                'lesson_id' => $session->lesson_id,
                'due_at' => $session->scheduled_at->copy()->subHours($leadHours),
            ]);
        }

        $this->info("Generated {$sessions->count()} session prep task(s).");

        return self::SUCCESS;
    }
}
