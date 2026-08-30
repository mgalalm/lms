<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\SessionType;
use App\Enums\SupportTicketStatus;
use App\Models\ClassSession;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketOpened;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_can_open_a_support_ticket_for_their_own_session(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $session = ClassSession::factory()->for($teacher, 'teacher')->create(['type' => SessionType::Physical]);

        $response = $this->actingAs($teacher)->post(route('teaching.support-tickets.store', $session), [
            'subject' => 'Frozen Video',
            'description' => 'The video was frozen and the student got disconnected.',
        ]);

        $ticket = SupportTicket::firstOrFail();

        $response->assertRedirect(route('teaching.support-tickets.show', $ticket));
        $this->assertSame($teacher->id, $ticket->teacher_id);
        $this->assertSame($session->id, $ticket->class_session_id);
        $this->assertSame(SupportTicketStatus::Open, $ticket->status);
        $this->assertNotNull($ticket->code);
        $this->assertSame(ChannelType::Support, $ticket->channel->type);
        $this->assertTrue($ticket->channel->users->contains($teacher));
        $this->assertTrue($ticket->channel->users->contains($admin));
        $this->assertSame(1, $ticket->channel->messages()->count());

        Notification::assertSentTo($admin, SupportTicketOpened::class);
    }

    public function test_teacher_cannot_open_a_ticket_for_a_session_they_do_not_teach(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $session = ClassSession::factory()->for($otherTeacher, 'teacher')->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.support-tickets.store', $session), [
                'subject' => 'Frozen Video',
                'description' => 'Issue description.',
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_view_their_own_ticket(): void
    {
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->create();
        $ticket->channel->users()->attach($teacher);

        $this->actingAs($teacher)
            ->get(route('teaching.support-tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('teacher/SupportTicketShow')
                ->where('ticket.code', $ticket->code));
    }

    public function test_teacher_cannot_view_someone_elses_ticket(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($otherTeacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->get(route('teaching.support-tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_teacher_can_resolve_their_own_ticket(): void
    {
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->patch(route('teaching.support-tickets.resolve', $ticket))
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame(SupportTicketStatus::Resolved, $ticket->status);
        $this->assertSame($teacher->id, $ticket->resolved_by);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_teacher_can_rate_their_own_resolved_ticket(): void
    {
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->resolved()->create();

        $this->actingAs($teacher)
            ->post(route('teaching.support-tickets.feedback', $ticket), [
                'rating' => 4,
                'comment' => 'Handled quickly, thanks!',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame(4, $ticket->rating);
        $this->assertSame('Handled quickly, thanks!', $ticket->comment);
        $this->assertNotNull($ticket->rated_at);
    }

    public function test_a_rating_is_required_to_submit_ticket_feedback(): void
    {
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->resolved()->create();

        $this->actingAs($teacher)
            ->post(route('teaching.support-tickets.feedback', $ticket), ['comment' => 'No rating given.'])
            ->assertSessionHasErrors('rating');

        $this->assertNull($ticket->refresh()->rating);
    }

    public function test_a_teacher_cannot_rate_an_open_ticket(): void
    {
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->post(route('teaching.support-tickets.feedback', $ticket), ['rating' => 5])
            ->assertStatus(422);

        $this->assertNull($ticket->refresh()->rating);
    }

    public function test_a_teacher_cannot_rate_someone_elses_ticket(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($otherTeacher, 'teacher')->resolved()->create();

        $this->actingAs($teacher)
            ->post(route('teaching.support-tickets.feedback', $ticket), ['rating' => 5])
            ->assertForbidden();

        $this->assertNull($ticket->refresh()->rating);
    }

    public function test_teacher_can_reply_in_their_ticket_thread_via_the_community_message_route(): void
    {
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->create();
        $ticket->channel->users()->attach($teacher);

        $this->actingAs($teacher)
            ->post(route('community.messages.store', $ticket->channel), [
                'type' => 'text',
                'body' => 'Still happening.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $ticket->channel->messages()->count());
    }

    public function test_support_channels_are_excluded_from_the_community_sidebar(): void
    {
        $teacher = User::factory()->teacher()->create();
        $ticket = SupportTicket::factory()->for($teacher, 'teacher')->create();
        $ticket->channel->users()->attach($teacher);

        $this->actingAs($teacher)
            ->get(route('community.index'))
            ->assertInertia(fn ($page) => $page->where(
                'channels',
                fn ($channels) => collect($channels)->doesntContain(fn ($channel) => $channel['id'] === $ticket->channel_id)
            ));
    }
}
