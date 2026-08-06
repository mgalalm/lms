<?php

namespace App\Filament\Resources\SessionReviews;

use App\Filament\Resources\SessionReviews\Pages\CreateSessionReview;
use App\Filament\Resources\SessionReviews\Pages\EditSessionReview;
use App\Filament\Resources\SessionReviews\Pages\ListSessionReviews;
use App\Filament\Resources\SessionReviews\Schemas\SessionReviewForm;
use App\Filament\Resources\SessionReviews\Tables\SessionReviewsTable;
use App\Models\SessionReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SessionReviewResource extends Resource
{
    protected static ?string $model = SessionReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Insights';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['classSession.teacher', 'classSession.student', 'classSession.lesson']);
    }

    public static function form(Schema $schema): Schema
    {
        return SessionReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSessionReviews::route('/'),
            'create' => CreateSessionReview::route('/create'),
            'edit' => EditSessionReview::route('/{record}/edit'),
        ];
    }
}
