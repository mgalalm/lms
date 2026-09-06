<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parent Ping SMS Copy
    |--------------------------------------------------------------------------
    |
    | The SMS leg of "Ping Parent" — kept here rather than inline so it can
    | be tuned without touching code. Keep it under 160 characters where
    | possible once :student/:lesson/:time are filled in. The email leg
    | reuses App\Notifications\ParentPinged's existing inline content
    | instead of duplicating a template here.
    |
    */

    'ping' => [
        'sms' => ':student has an update from their tutor re: ":lesson" (:time). Check your email for the full message.',
    ],

];
