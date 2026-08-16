<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\ClassSession;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationalStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -50;

    protected function getStats(): array
    {
        return [
            Stat::make('Active Students', User::role(UserRole::Student->value)->count()),
            Stat::make('Active Teachers', User::role(UserRole::Teacher->value)->count()),
            Stat::make('Sessions Today', ClassSession::whereDate('scheduled_at', today())->count()),
            Stat::make('Sessions This Week', ClassSession::whereBetween(
                'scheduled_at',
                [now()->startOfWeek(), now()->endOfWeek()]
            )->count()),
        ];
    }
}
