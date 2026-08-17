<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            // Explicit order = operational priority. Safety sits right under
            // the dashboard on purpose — flagged content review must never
            // be buried behind day-to-day admin.
            ->navigationGroups([
                NavigationGroup::make('Safety')
                    ->extraSidebarAttributes(['title' => 'Messages automatically hidden pending moderation review. Audit Log tracks every access to child-safety-sensitive data.']),
                NavigationGroup::make('Sessions')
                    ->extraSidebarAttributes(['title' => 'The session schedule, plus student and teacher profiles.']),
                NavigationGroup::make('Curriculum'),
                NavigationGroup::make('Payments')
                    ->extraSidebarAttributes(['title' => 'Session Packages = products for sale. Purchases = individual transactions. Session Credits Ledger = each student\'s running balance.']),
                NavigationGroup::make('Payroll & Requests')
                    ->extraSidebarAttributes(['title' => 'Teacher pay, leave balances, and HR-style requests.']),
                NavigationGroup::make('Insights'),
                NavigationGroup::make('Gamification'),
                NavigationGroup::make('Support'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                //
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
