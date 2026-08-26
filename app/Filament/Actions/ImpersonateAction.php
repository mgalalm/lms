<?php

namespace App\Filament\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Shared "Log in as" row action for any Filament record with a `user`
 * relation (StudentProfile, TeacherProfile) — a plain link to
 * ImpersonationController::store() rather than a Livewire action, so it's
 * just a normal authenticated browser navigation, no AJAX/redirect
 * plumbing needed.
 */
class ImpersonateAction
{
    public static function make(): Action
    {
        return Action::make('impersonate')
            ->label('Log in as')
            ->icon(Heroicon::OutlinedUserCircle)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('You will be logged in as this user until you click "Return to Admin". Every impersonation is logged for audit purposes.')
            ->visible(fn ($record) => $record->user instanceof User && ! $record->user->hasRole(UserRole::Admin->value))
            ->url(fn ($record) => route('impersonate.start', $record->user));
    }
}
