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

];
