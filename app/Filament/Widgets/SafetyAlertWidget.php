<?php

namespace App\Filament\Widgets;

use App\Enums\MessageModerationStatus;
use App\Filament\Resources\FlaggedMessages\FlaggedMessageResource;
use App\Models\Message;
use Filament\Widgets\Widget;

class SafetyAlertWidget extends Widget
{
    protected string $view = 'filament.widgets.safety-alert-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * Lowest sort value on the dashboard — nothing renders above a live
     * safety alert.
     */
    protected static ?int $sort = -100;

    /**
     * Only shown when there's something to act on — an empty banner is
     * noise, not signal.
     */
    public static function canView(): bool
    {
        return static::flaggedCount() > 0;
    }

    public function getFlaggedCount(): int
    {
        return static::flaggedCount();
    }

    public function getFlaggedMessagesUrl(): string
    {
        return FlaggedMessageResource::getUrl('index');
    }

    private static function flaggedCount(): int
    {
        return Message::where('moderation_status', MessageModerationStatus::Flagged)->count();
    }
}
