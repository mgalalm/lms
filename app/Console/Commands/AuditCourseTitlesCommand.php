<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;

/**
 * One-time pre-flight check before the migration that drops Course.title
 * (courses collapse into grade_band alone — see docs/design decisions).
 * Not wired into the migration itself, which must stay non-interactive —
 * run this by hand first and only proceed once it reports clean.
 */
class AuditCourseTitlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:audit-titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for courses whose title differs meaningfully from their grade_band before the title column is dropped';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $flagged = Course::query()->get()->filter(function (Course $course) {
            $title = mb_strtolower(trim((string) $course->title));
            $gradeBand = mb_strtolower(trim((string) $course->grade_band));

            return $title !== '' && $title !== $gradeBand;
        });

        if ($flagged->isEmpty()) {
            $this->info('No courses have a title that differs from their grade_band — safe to proceed.');

            return self::SUCCESS;
        }

        $this->error("Found {$flagged->count()} course(s) with a title that would be lost:");

        $this->table(
            ['id', 'title', 'grade_band'],
            $flagged->map(fn (Course $course) => [$course->id, $course->title, $course->grade_band])->all(),
        );

        $this->warn('Aborting — review these before dropping the title column. Nothing was changed.');

        return self::FAILURE;
    }
}
