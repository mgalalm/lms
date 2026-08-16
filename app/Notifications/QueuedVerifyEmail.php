<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Laravel's built-in VerifyEmail notification sends synchronously, so a
 * mail-provider failure (e.g. Mailgun misconfigured/down) throws mid
 * request and 500s registration — even though the account already saved.
 * Every other notification in this app is queued for exactly this
 * reason; this makes email verification consistent with them.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
