<?php

namespace App\Filament\Resources\FeedbackTemplates\Tables;

use App\Enums\FeedbackSentiment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeedbackTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sentiment')
                    ->badge()
                    ->color(fn (FeedbackSentiment $state) => match ($state) {
                        FeedbackSentiment::Positive => 'success',
                        FeedbackSentiment::Neutral => 'gray',
                        FeedbackSentiment::NeedsImprovement => 'warning',
                    }),
                TextColumn::make('body')
                    ->limit(60),
                TextColumn::make('sort_order')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sentiment')
                    ->options(FeedbackSentiment::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
