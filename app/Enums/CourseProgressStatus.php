<?php

namespace App\Enums;

enum CourseProgressStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case QuizPending = 'quiz_pending';
    case Completed = 'completed';

    public static function resolve(int $completedLessons, int $totalLessons, bool $certified): self
    {
        if ($certified) {
            return self::Completed;
        }

        if ($totalLessons > 0 && $completedLessons >= $totalLessons) {
            return self::QuizPending;
        }

        if ($completedLessons > 0) {
            return self::InProgress;
        }

        return self::NotStarted;
    }

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::QuizPending => 'Quiz Pending',
            self::Completed => 'Completed',
        };
    }

    /**
     * The filter-tab bucket this status belongs to — the LMS index page
     * only exposes Not Started / In Progress / Completed tabs, with
     * Quiz Pending folded into In Progress.
     */
    public function filterCategory(): string
    {
        return match ($this) {
            self::NotStarted => 'not_started',
            self::InProgress, self::QuizPending => 'in_progress',
            self::Completed => 'completed',
        };
    }
}
