<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ParentPingLogs\Pages\ListParentPingLogs;
use App\Filament\Resources\ParentPingLogs\ParentPingLogResource;
use App\Models\ParentPingLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ParentPingLogResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_list_ping_logs(): void
    {
        $admin = $this->admin();
        ParentPingLog::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListParentPingLogs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(ParentPingLog::all());
    }

    public function test_admin_can_view_a_ping_log(): void
    {
        $admin = $this->admin();
        $log = ParentPingLog::factory()->create();

        $this->actingAs($admin)
            ->get(ParentPingLogResource::getUrl('view', ['record' => $log]))
            ->assertOk();
    }

    public function test_ping_logs_cannot_be_created_edited_or_deleted(): void
    {
        $this->assertFalse(ParentPingLogResource::canCreate());
        $this->assertFalse(ParentPingLogResource::canEdit(ParentPingLog::factory()->make()));
        $this->assertFalse(ParentPingLogResource::canDelete(ParentPingLog::factory()->make()));
    }

    public function test_a_teacher_cannot_access_the_admin_panel(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(ParentPingLogResource::getUrl('index'))
            ->assertForbidden();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
