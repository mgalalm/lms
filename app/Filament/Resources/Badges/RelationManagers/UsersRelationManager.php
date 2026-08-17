<?php

namespace App\Filament\Resources\Badges\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Awarded To';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                // Not ->searchable(): User.name is encrypted at rest.
                TextColumn::make('name'),
                TextColumn::make('pivot.awarded_at')
                    ->label('Awarded at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    // The attach modal's picker searches by
                    // recordTitleAttribute() by default — 'name' is
                    // encrypted, so redirect the search to the
                    // still-plaintext 'email' column instead.
                    ->recordSelectSearchColumns(['email'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        DateTimePicker::make('awarded_at')
                            ->default(now())
                            ->required(),
                    ]),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
