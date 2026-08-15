<?php

namespace App\Observers;

use App\Jobs\ModerateMessageJob;
use App\Models\Message;

class MessageObserver
{
    /**
     * Handle the Message "created" event.
     */
    public function created(Message $message): void
    {
        ModerateMessageJob::dispatch($message);
    }
}
