<?php

namespace Tests\Feature\Filament;

use App\Enums\SettingKey;
use App\Filament\Pages\Settings as SettingsPage;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_setting_falls_back_to_its_default_when_never_saved(): void
    {
        $this->assertSame(0, Setting::count());
        $this->assertSame(2, Setting::get(SettingKey::QuizMaxGenerations));
    }

    public function test_a_saved_setting_round_trips_with_its_type_intact(): void
    {
        Setting::set(SettingKey::QuizMaxGenerations, 5);

        // Not a string "5" — the json cast has to survive the cached read,
        // which a query-builder pluck() would have bypassed.
        $this->assertSame(5, Setting::get(SettingKey::QuizMaxGenerations));
    }

    public function test_writing_a_setting_busts_the_cache(): void
    {
        $this->assertSame(2, Setting::get(SettingKey::QuizMaxGenerations));

        Setting::set(SettingKey::QuizMaxGenerations, 7);

        $this->assertSame(7, Setting::get(SettingKey::QuizMaxGenerations));
    }

    public function test_an_admin_can_view_and_save_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SettingsPage::class)
            ->assertFormSet([SettingKey::QuizMaxGenerations->name => 2])
            ->fillForm([SettingKey::QuizMaxGenerations->name => 4])
            ->call('save')
            ->assertNotified('Settings saved');

        $this->assertSame(4, Setting::get(SettingKey::QuizMaxGenerations));
    }

    public function test_the_settings_page_rejects_a_value_below_one(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SettingsPage::class)
            ->fillForm([SettingKey::QuizMaxGenerations->name => 0])
            ->call('save')
            ->assertHasFormErrors([SettingKey::QuizMaxGenerations->name]);

        $this->assertSame(2, Setting::get(SettingKey::QuizMaxGenerations));
    }

    public function test_a_non_admin_cannot_reach_the_settings_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(SettingsPage::getUrl())
            ->assertForbidden();
    }
}
