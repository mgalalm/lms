<?php

namespace App\Filament\Resources\StudentProfiles;

use App\Models\StudentProfile;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/**
 * Shared side effects for admin actions, reused by both the table's row
 * action and the EditRecord page's header action.
 */
class StudentProfileActions
{
    public static function erase(StudentProfile $studentProfile): void
    {
        $studentProfile->user->gdprErase();
    }

    /**
     * The "Erase (GDPR)" action itself — requires the admin to type the
     * student's code to confirm, given how irreversible this is.
     */
    public static function eraseAction(): Action
    {
        return Action::make('gdprErase')
            ->label('Erase (GDPR)')
            ->icon(Heroicon::OutlinedShieldExclamation)
            ->color('danger')
            ->visible(fn (StudentProfile $record) => ! $record->user->gdpr_erased_at)
            ->schema([
                TextInput::make('confirm_code')
                    ->label('Type the student code to confirm')
                    ->required()
                    ->rules([
                        fn (StudentProfile $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                            if ($value !== $record->code) {
                                $fail('That doesn\'t match this student\'s code.');
                            }
                        },
                    ])
                    ->helperText(fn (StudentProfile $record) => "This student's code is {$record->code}."),
            ])
            ->modalHeading('Erase this student\'s personal data?')
            ->modalDescription('This scrubs their name, email, login credentials, and parent/guardian contact details — irreversible. Session history and payment records are kept for financial/audit purposes but will no longer show identifying information.')
            ->modalSubmitActionLabel('Erase')
            ->action(fn (StudentProfile $record) => self::erase($record));
    }
}
