<?php

namespace App\Filament\Pages;

use App\Enums\SettingKey;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Application settings, driven entirely by the SettingKey enum — adding a
 * case there surfaces a new field here without touching this page, beyond
 * giving it a form component in componentFor().
 */
class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            collect(SettingKey::cases())
                ->mapWithKeys(fn (SettingKey $key) => [$key->name => Setting::get($key)])
                ->all()
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                collect(SettingKey::cases())
                    ->groupBy(fn (SettingKey $key) => $key->section())
                    ->map(fn ($keys, string $section) => Section::make($section)
                        ->schema($keys->map(fn (SettingKey $key) => $this->componentFor($key))->all()))
                    ->values()
                    ->all()
            )
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (SettingKey::cases() as $key) {
            Setting::set($key, $this->castValue($key, $data[$key->name] ?? null));
        }

        Notification::make()->title('Settings saved')->success()->send();
    }

    /**
     * The form component for a given setting. Keyed on the enum case rather
     * than the raw key string so a renamed key doesn't silently fall through
     * to an untyped default.
     */
    private function componentFor(SettingKey $key): TextInput
    {
        return match ($key) {
            SettingKey::QuizMaxGenerations,
            SettingKey::QuizMaxAttempts => TextInput::make($key->name)
                ->label($key->label())
                ->helperText($key->helperText())
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(20),
            SettingKey::QuizPassingScore => TextInput::make($key->name)
                ->label($key->label())
                ->helperText($key->helperText())
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(100)
                ->suffix('%'),
        };
    }

    private function castValue(SettingKey $key, mixed $value): mixed
    {
        return match ($key) {
            SettingKey::QuizMaxGenerations,
            SettingKey::QuizPassingScore,
            SettingKey::QuizMaxAttempts => (int) $value,
        };
    }
}
