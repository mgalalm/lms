<?php

namespace App\Filament\Pages;

use App\Models\SessionPackage;
use App\Services\Stripe\PromotionCodeClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use UnitEnum;

class PromoCodes extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.pages.promo-codes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $navigationLabel = 'Promo Codes';

    protected static ?string $title = 'Promo Codes';

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 43;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage) {
                $codes = app(PromotionCodeClient::class)->list();

                return new LengthAwarePaginator(
                    $codes->forPage($page, $recordsPerPage),
                    $codes->count(),
                    $recordsPerPage,
                    $page,
                );
            })
            ->columns([
                TextColumn::make('code')
                    ->weight('bold'),
                TextColumn::make('discount'),
                IconColumn::make('active')
                    ->boolean(),
                TextColumn::make('times_redeemed')
                    ->label('Redemptions')
                    ->formatStateUsing(fn (array $record) => $record['times_redeemed'].' / '.($record['max_redemptions'] ?? '∞')),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never'),
                TextColumn::make('created')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (array $record) => $record['active'])
                    ->requiresConfirmation()
                    ->action(function (array $record) {
                        app(PromotionCodeClient::class)->deactivate($record['id']);
                        $this->resetTable();
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Promo Code')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->alphaDash()
                        ->maxLength(255)
                        ->default(fn () => Str::upper(Str::random(8)))
                        ->helperText('The code customers type in at checkout, e.g. SUMMER25.'),
                    Select::make('discount_type')
                        ->label('Discount Type')
                        ->options([
                            'percentage' => 'Percentage off',
                            'fixed' => 'Fixed amount off',
                        ])
                        ->required()
                        ->live()
                        ->default('percentage'),
                    TextInput::make('discount_value')
                        ->label(fn ($get) => $get('discount_type') === 'fixed' ? 'Amount Off (cents)' : 'Percent Off')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(fn ($get) => $get('discount_type') === 'percentage' ? 100 : null)
                        ->required()
                        ->helperText(fn ($get) => $get('discount_type') === 'fixed'
                            ? 'Enter the amount in cents, e.g. 1000 for €10.00 off.'
                            : 'A whole or decimal percentage, e.g. 25 for 25% off.'),
                    Select::make('session_package_ids')
                        ->label('Applies To')
                        ->multiple()
                        ->options(fn () => SessionPackage::query()->pluck('name', 'id'))
                        ->helperText('Leave blank to apply to all session packages.'),
                    DatePicker::make('expires_at')
                        ->label('Expiry Date')
                        ->minDate(now()),
                    TextInput::make('max_redemptions')
                        ->label('Max Redemptions')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Leave blank for unlimited redemptions.'),
                ])
                ->action(function (array $data) {
                    app(PromotionCodeClient::class)->create([
                        'code' => $data['code'],
                        'discount_type' => $data['discount_type'],
                        'discount_value' => (int) $data['discount_value'],
                        'session_package_ids' => $data['session_package_ids'] ?? [],
                        'expires_at' => $data['expires_at'] ? Carbon::parse($data['expires_at']) : null,
                        'max_redemptions' => $data['max_redemptions'] ? (int) $data['max_redemptions'] : null,
                    ]);

                    $this->resetTable();
                }),
        ];
    }
}
