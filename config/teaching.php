<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Teacher Task Intervals
    |--------------------------------------------------------------------------
    |
    | How long before a session's start a session_prep task is due, and how
    | long after a session ends a session_review task is due.
    |
    */

    'task_prep_lead_hours' => env('TEACHING_TASK_PREP_LEAD_HOURS', 24),

    'task_review_due_hours' => env('TEACHING_TASK_REVIEW_DUE_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Teaching Feed
    |--------------------------------------------------------------------------
    |
    | How many days a pending project submission can sit without review
    | before it's treated as overdue (moved into the "Needs Attention"
    | group) on the /teaching feed.
    |
    */

    'project_review_overdue_days' => env('TEACHING_PROJECT_REVIEW_OVERDUE_DAYS', 3),

];
