<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ParentNotificationType: string implements HasLabel
{
    case UrgentPing = 'urgent_ping';
    case SessionReminder = 'session_reminder';
    case Feedback = 'feedback';
    case ProgressUpdate = 'progress_update';
    case Marketing = 'marketing';

    public function getLabel(): string
    {
        return match ($this) {
            self::UrgentPing => 'Urgent ping from a teacher',
            self::SessionReminder => 'Session reminders',
            self::Feedback => 'Session feedback',
            self::ProgressUpdate => 'Progress updates',
            self::Marketing => 'Marketing',
        };
    }

    /**
     * The channel a parent's preference starts on before they ever touch
     * the preference page. Marketing MUST default to None — GDPR requires
     * separate, explicit opt-in, never an opt-out default.
     */
    public function defaultChannel(): ParentNotificationChannel
    {
        return match ($this) {
            self::UrgentPing => ParentNotificationChannel::Sms,
            self::SessionReminder, self::Feedback, self::ProgressUpdate => ParentNotificationChannel::Email,
            self::Marketing => ParentNotificationChannel::None,
        };
    }
}
