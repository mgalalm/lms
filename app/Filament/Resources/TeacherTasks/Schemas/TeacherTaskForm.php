<?php

namespace App\Filament\Resources\TeacherTasks\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * The Edit form — each admin-assigned task is its own independent row per
 * teacher (see CreateTeacherTask, which fans a single submission out into
 * one row per selected teacher), so editing is always single-teacher.
 * Type isn't editable here: changing a system-generated session_prep/
 * session_review task's type would leave it inconsistent with the
 * class_session/lesson it's linked to.
 */
class TeacherTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->relationship('teacher', 'name', fn ($query) => $query->role(UserRole::Teacher->value))
                    ->searchable()
                    ->required(),
                TextInput::make('type')
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                DateTimePicker::make('due_at')
                    ->required(),
            ]);
    }
}
