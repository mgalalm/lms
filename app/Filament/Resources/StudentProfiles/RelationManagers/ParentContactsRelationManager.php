<?php

namespace App\Filament\Resources\StudentProfiles\RelationManagers;

use App\Enums\ParentContactChannel;
use App\Exceptions\Parents\TooManyVerificationSendsException;
use App\Models\ParentContact;
use App\Services\Parents\ContactVerificationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A student hasOne parent contact today (parent_contacts.student_id has no
 * unique constraint, deliberately — hasMany can be added later). This
 * plugs into the User::parentContacts() HasMany purely for Filament's
 * RelationManager plumbing, which expects a HasMany; the CreateAction is
 * hidden once one exists to keep today's cardinality.
 */
class ParentContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'parentContacts';

    protected static ?string $title = 'Parent/Guardian Contact';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Phone')
                ->tel()
                ->maxLength(255),
            Checkbox::make('confirm_consent')
                ->label('I confirm consent has been obtained from this parent/guardian.')
                ->required()
                ->dehydrated(false)
                ->visible(fn (string $operation) => $operation === 'create'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->placeholder('Not set'),
                // Not ->searchable(): encrypted at rest.
                TextColumn::make('email')
                    ->placeholder('Not set'),
                IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean(),
                TextColumn::make('phone')
                    ->placeholder('Not set'),
                IconColumn::make('phone_verified_at')
                    ->label('Phone Verified')
                    ->boolean(),
                IconColumn::make('consent_given_at')
                    ->label('Consent')
                    ->boolean()
                    ->tooltip(fn (ParentContact $record) => $record->consent_given_at?->toDayDateTimeString() ?? 'Not recorded'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->parentContact === null)
                    ->mutateFormDataUsing(function (array $data) {
                        $data['consent_given_at'] = now();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resendEmailCode')
                    ->label('Resend email code')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('gray')
                    ->visible(fn (ParentContact $record) => $record->email && ! $record->email_verified_at)
                    ->action(fn (ParentContact $record) => $this->resend($record, ParentContactChannel::Email)),
                Action::make('resendPhoneCode')
                    ->label('Resend phone code')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->color('gray')
                    ->visible(fn (ParentContact $record) => $record->phone && ! $record->phone_verified_at)
                    ->action(fn (ParentContact $record) => $this->resend($record, ParentContactChannel::Phone)),
            ]);
    }

    private function resend(ParentContact $record, ParentContactChannel $channel): void
    {
        try {
            app(ContactVerificationService::class)->sendCode($record, $channel);

            Notification::make()->title('Verification code sent')->success()->send();
        } catch (TooManyVerificationSendsException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
