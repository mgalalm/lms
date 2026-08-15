<?php

namespace App\Filament\Resources\FlaggedMessages;

use App\Models\Message;

/**
 * Shared side effects for admin review actions, reused by both the
 * table's row action and the ViewRecord page's header action.
 */
class FlaggedMessageActions
{
    public static function approve(Message $message): void
    {
        $message->approveModeration();
    }
}
