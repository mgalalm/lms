<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Enums\UserRole;
use App\Models\TeacherCourseCompletion;
use App\Models\TeacherProfile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeacherCompletionsRelationManager extends RelationManager
{
    protected static string $relationship = 'teacherCompletions';

    protected static ?string $title = 'Teacher Certification';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_profile_id')
                    ->label('Teacher')
                    ->options(fn () => TeacherProfile::whereHas('user', fn ($query) => $query->role(UserRole::Teacher->value))
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn (TeacherProfile $profile) => [$profile->id => $profile->user->name]))
                    ->searchable()
                    ->required(),
                DateTimePicker::make('completed_at')
                    ->label('Completed at'),
                Toggle::make('quiz_passed')
                    ->label('Passed the quiz'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('teacherProfile.user.name')
                    ->label('Teacher')
                    ->searchable(),
                TextColumn::make('completed_at')
                    ->label('Completed at')
                    ->dateTime()
                    ->placeholder('Not completed'),
                IconColumn::make('quiz_passed')
                    ->label('Quiz passed')
                    ->boolean(),
                IconColumn::make('certified')
                    ->label('Certified to teach')
                    ->boolean()
                    ->getStateUsing(fn (TeacherCourseCompletion $record) => $record->isCertified()),
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
