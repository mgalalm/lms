<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Models\Channel;
use App\Models\ClassSession;
use App\Models\Message;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesTable
{
    /**
     * Maps a subject's model class to the human label shown in the table
     * and the "Subject Type" filter.
     *
     * @var array<class-string, string>
     */
    public const SUBJECT_TYPE_LABELS = [
        ClassSession::class => 'Recording',
        Channel::class => 'Chat History',
        Message::class => 'Flagged Message Review',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // Not ->searchable()/->sortable(): User.name is encrypted
                // at rest — filter/search by causer via the "Accessed By"
                // filter (email) instead.
                TextColumn::make('causer.name')
                    ->label('Accessed By')
                    ->placeholder('System'),
                TextColumn::make('description')
                    ->label('Action'),
                TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => self::SUBJECT_TYPE_LABELS[$state] ?? $state),
                TextColumn::make('properties.ip')
                    ->label('IP Address'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('causer_id')
                    ->label('Accessed By')
                    ->options(fn () => User::query()->orderBy('email')->pluck('email', 'id'))
                    ->searchable(),
                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options(self::SUBJECT_TYPE_LABELS),
                Filter::make('created_at')
                    ->label('Date Range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
