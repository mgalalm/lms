<?php

namespace Tests\Feature\Filament;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketReplied;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_reply_to_a_ticket_and_notifies_the_teacher(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->create();
        $ticket->channel->users()->attach([$admin->id, $teacher->id]);

        $this->actingAs($admin);

        Livewire::test(ListSupportTickets::class)
            ->callTableAction('reply', $ticket, data: ['body' => 'Looking into it now.']);

        $this->assertSame(1, $ticket->channel->messages()->count());
        $this->assertSame($admin->id, $ticket->channel->messages()->first()->user_id);

        Notification::assertSentTo($teacher, SupportTicketReplied::class);
    }

    public function test_admin_can_resolve_and_reopen_a_ticket(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($admin);

        Livewire::test(ListSupportTickets::class)
            ->callTableAction('toggleStatus', $ticket);

        $ticket->refresh();
        $this->assertSame(SupportTicketStatus::Resolved, $ticket->status);
        $this->assertSame($admin->id, $ticket->resolved_by);

        Livewire::test(ListSupportTickets::class)
            ->callTableAction('toggleStatus', $ticket);

        $ticket->refresh();
        $this->assertSame(SupportTicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->resolved_by);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
