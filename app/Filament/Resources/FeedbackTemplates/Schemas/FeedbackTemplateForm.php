<?php

namespace App\Filament\Resources\FeedbackTemplates\Schemas;

use App\Enums\FeedbackSentiment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FeedbackTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Select::make('sentiment')
                    ->options(FeedbackSentiment::class)
                    ->required(),
                Textarea::make('body')
                    ->label('Template Text')
                    ->helperText('Populates the feedback comment box when a teacher picks this template — they can edit it afterward.')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
