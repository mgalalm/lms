<?php

namespace App\Filament\Resources\TeacherRequests\Tables;

use App\Enums\TeacherRequestStatus;
use App\Enums\TeacherRequestType;
use App\Models\LeaveCredit;
use App\Models\TeacherRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeacherRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Not ->searchable()/->sortable(): User.name is encrypted
                // at rest.
                TextColumn::make('teacherProfile.user.name')
                    ->label('Teacher'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (TeacherRequestStatus $state) => match ($state) {
                        TeacherRequestStatus::Pending => 'warning',
                        TeacherRequestStatus::Approved => 'success',
                        TeacherRequestStatus::Rejected => 'danger',
                    }),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed by')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(TeacherRequestType::class),
                SelectFilter::make('status')
                    ->options(TeacherRequestStatus::class),
            ])
            ->recordActions([
                Action::make('approve')
                    ->visible(fn (TeacherRequest $record) => $record->status === TeacherRequestStatus::Pending)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (TeacherRequest $record) {
                        $record->update([
                            'status' => TeacherRequestStatus::Approved,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        if (in_array($record->type, [TeacherRequestType::Leave, TeacherRequestType::ExcusedTime], true)) {
                            $days = $record->dayCount();
                            $credit = LeaveCredit::firstOrCreate(['teacher_profile_id' => $record->teacher_profile_id]);
                            $credit->increment('monthly_used', $days);
                            $credit->increment('annual_used', $days);
                        }

                        Notification::make()->title('Request approved')->success()->send();
                    }),
                Action::make('reject')
                    ->visible(fn (TeacherRequest $record) => $record->status === TeacherRequestStatus::Pending)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('review_notes')->label('Reason'),
                    ])
                    ->action(function (TeacherRequest $record, array $data) {
                        $record->update([
                            'status' => TeacherRequestStatus::Rejected,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'review_notes' => $data['review_notes'] ?? null,
                        ]);

                        Notification::make()->title('Request rejected')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
