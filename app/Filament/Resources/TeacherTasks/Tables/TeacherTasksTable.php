<?php

namespace App\Filament\Resources\TeacherTasks\Tables;

use App\Enums\TeacherTaskType;
use App\Enums\UserRole;
use App\Models\TeacherTask;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('due_at')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (TeacherTaskType $state) => match ($state) {
                        TeacherTaskType::SessionPrep => 'info',
                        TeacherTaskType::SessionReview => 'warning',
                        TeacherTaskType::Manual => 'gray',
                    }),
                TextColumn::make('due_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->state(fn (TeacherTask $record) => match (true) {
                        $record->completed_at !== null => 'Completed',
                        $record->isOverdue() => 'Overdue',
                        default => 'Pending',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Completed' => 'success',
                        'Overdue' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(TeacherTaskType::class),
                SelectFilter::make('teacher_id')
                    ->label('Teacher')
                    ->relationship('teacher', 'name', fn ($query) => $query->role(UserRole::Teacher->value))
                    ->searchable(),
                Filter::make('status')
                    ->schema([
                        Select::make('value')->options([
                            'pending' => 'Pending',
                            'overdue' => 'Overdue',
                            'completed' => 'Completed',
                        ]),
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'pending' => $query->whereNull('completed_at'),
                        'overdue' => $query->whereNull('completed_at')->where('due_at', '<', now()),
                        'completed' => $query->whereNotNull('completed_at'),
                        default => $query,
                    }),
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
