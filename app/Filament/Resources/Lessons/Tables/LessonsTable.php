<?php

namespace App\Filament\Resources\Lessons\Tables;

use App\Enums\LessonVideoStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('video'))
            ->columns([
                TextColumn::make('module.title')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('video.status')
                    ->label('Video')
                    ->badge()
                    ->placeholder('No video')
                    ->color(fn (?LessonVideoStatus $state) => match ($state) {
                        LessonVideoStatus::Uploading => 'gray',
                        LessonVideoStatus::Processing => 'warning',
                        LessonVideoStatus::Draft => 'info',
                        LessonVideoStatus::Published => 'success',
                        null => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
