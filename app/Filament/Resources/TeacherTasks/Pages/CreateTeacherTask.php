<?php

namespace App\Filament\Resources\TeacherTasks\Pages;

use App\Enums\TeacherTaskType;
use App\Enums\UserRole;
use App\Filament\Resources\TeacherTasks\TeacherTaskResource;
use App\Models\TeacherTask;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateTeacherTask extends CreateRecord
{
    protected static string $resource = TeacherTaskResource::class;

    /**
     * Overrides the resource's default (single-teacher, Edit-oriented)
     * form — creation fans a task out to one or many teachers at once,
     * so this needs its own multi-select instead of the bound teacher_id.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_ids')
                    ->label('Teachers')
                    ->multiple()
                    ->options(User::role(UserRole::Teacher->value)->pluck('name', 'id'))
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                DateTimePicker::make('due_at')
                    ->required(),
            ]);
    }

    /**
     * One row per selected teacher, not one row with a pivot — teacher_id
     * is a plain FK (see the migration), by design: each teacher's copy
     * completes independently.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $tasks = collect($data['teacher_ids'])->map(fn ($teacherId) => TeacherTask::create([
            'teacher_id' => $teacherId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => TeacherTaskType::Manual,
            'due_at' => $data['due_at'],
            'created_by' => auth()->id(),
        ]));

        return $tasks->first();
    }
}
