<?php

namespace App\Filament\Resources\PayrollEntries\RelationManagers;

use App\Enums\PayrollAdjustmentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdjustmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'adjustments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(PayrollAdjustmentType::class)
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('EGP'),
                TextInput::make('reason')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (PayrollAdjustmentType $state) => $state === PayrollAdjustmentType::Bonus ? 'success' : 'danger'),
                TextColumn::make('amount')
                    ->money('EGP'),
                TextColumn::make('reason'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
