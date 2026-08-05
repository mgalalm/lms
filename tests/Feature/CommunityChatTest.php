<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommunityChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function directChannel(User $a, User $b): Channel
    {
        $channel = Channel::factory()->create(['type' => ChannelType::Direct]);
        $channel->users()->attach([$a->id, $b->id]);

        return $channel;
    }

    public function test_index_only_lists_the_users_own_channels(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $this->directChannel($teacher, $student);
        $this->directChannel($teacher, $otherStudent);

        $this->actingAs($student)
            ->get(route('community.index'))
            ->assertInertia(fn (Assert $page) => $page->component('community/Index')->count('channels', 1));
    }

    public function test_a_participant_can_view_a_channel(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($student)
            ->get(route('community.show', $channel))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('community/Index')->where('selectedChannel.id', $channel->id));
    }

    public function test_a_non_participant_cannot_view_a_channel(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($outsider)
            ->get(route('community.show', $channel))
            ->assertForbidden();
    }

    public function test_viewing_a_channel_marks_it_read(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $channel->messages()->create([
            'user_id' => $teacher->id,
            'type' => 'text',
            'body' => '<p>hello</p>',
        ]);

        $this->actingAs($student)->get(route('community.show', $channel));

        $this->assertNotNull(
            $student->channels()->wherePivot('channel_id', $channel->id)->first()->pivot->last_read_at
        );
    }

    public function test_a_participant_can_post_a_text_message(): void
    {
        Event::fake([MessageSent::class]);

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)
            ->post(route('community.messages.store', $channel), [
                'type' => 'text',
                'body' => '<p>Hello there</p>',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'channel_id' => $channel->id,
            'user_id' => $teacher->id,
            'body' => '<p>Hello there</p>',
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_a_participant_can_post_a_task_card(): void
    {
        Event::fake([MessageSent::class]);

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)
            ->post(route('community.messages.store', $channel), [
                'type' => 'task_card',
                'meta' => ['title' => 'Reminder: for next time'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'channel_id' => $channel->id,
            'type' => 'task_card',
        ]);
    }

    public function test_a_task_card_requires_a_title(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)
            ->post(route('community.messages.store', $channel), ['type' => 'task_card'])
            ->assertInvalid(['meta.title']);
    }

    public function test_a_non_participant_cannot_post_a_message(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($outsider)
            ->post(route('community.messages.store', $channel), ['type' => 'text', 'body' => 'hi'])
            ->assertForbidden();
    }

    public function test_message_body_is_sanitized_on_store(): void
    {
        Event::fake([MessageSent::class]);

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)->post(route('community.messages.store', $channel), [
            'type' => 'text',
            'body' => '<p onclick="alert(1)">Hi <script>alert(2)</script><strong>there</strong></p>',
        ]);

        $message = Message::first();

        $this->assertSame('<p>Hi <strong>there</strong></p>', $message->body);
    }

    public function test_unread_count_reflects_messages_since_last_read(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $channel->messages()->create(['user_id' => $teacher->id, 'type' => 'text', 'body' => 'one']);
        $channel->messages()->create(['user_id' => $teacher->id, 'type' => 'text', 'body' => 'two']);

        $this->actingAs($student)
            ->get(route('community.index'))
            ->assertInertia(fn (Assert $page) => $page->where('channels.0.unread_count', 2));

        // Viewing the channel marks it read, so a fresh index load should show zero.
        $this->actingAs($student)->get(route('community.show', $channel));

        $this->actingAs($student)
            ->get(route('community.index'))
            ->assertInertia(fn (Assert $page) => $page->where('channels.0.unread_count', 0));
    }

    /**
     * The "log" broadcaster used in tests treats /broadcasting/auth as a
     * no-op success, so exercise the real (network-free) Pusher-protocol
     * auth() implementation that Reverb uses. Channel patterns are attached
     * to whichever connection is default *at registration time*, so
     * switching the default here means the pattern has to be re-registered
     * against the newly-resolved driver instance.
     */
    private function useRealBroadcastAuth(): void
    {
        config(['broadcasting.default' => 'reverb']);

        Broadcast::channel('channel.{channel}', function (User $user, Channel $channel) {
            return $user->can('view', $channel);
        });
    }

    public function test_broadcasting_auth_allows_participants(): void
    {
        $this->useRealBroadcastAuth();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($student)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-channel.{$channel->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertOk();
    }

    public function test_broadcasting_auth_rejects_non_participants(): void
    {
        $this->useRealBroadcastAuth();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($outsider)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-channel.{$channel->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertForbidden();
    }
}
