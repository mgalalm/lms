<?php

namespace App\Filament\Widgets;

use App\Enums\SessionPackagePurchaseStatus;
use App\Models\SessionPackagePurchase;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -40;

    protected function getStats(): array
    {
        // Sourced from session_package_purchases, which is the local
        // record of what Stripe/Cashier's webhook confirmed as paid — not
        // a separate query against Stripe's API.
        $paidThisMonth = SessionPackagePurchase::query()
            ->where('status', SessionPackagePurchaseStatus::Paid)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year);

        $count = (clone $paidThisMonth)->count();
        $revenueCents = (clone $paidThisMonth)->sum('price_cents');

        return [
            Stat::make('Packages Sold This Month', $count),
            // This app is EUR-only in practice (CASHIER_CURRENCY=eur, Irish/EU students).
            Stat::make('Revenue This Month', number_format($revenueCents / 100, 2).' EUR'),
        ];
    }
}
