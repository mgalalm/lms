<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\MessageModerationStatus;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageFlaggedForReview;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessageModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config(['services.openai.key' => 'test-key']);
    }

    public function test_a_clean_message_is_approved_and_visible_to_the_other_participant(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'results' => [[
                    'flagged' => false,
                    'categories' => ['harassment' => false, 'sexual' => false],
                ]],
            ]),
        ]);

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)->post(route('community.messages.store', $channel), [
            'type' => 'text',
            'body' => '<p>See you at 4pm!</p>',
        ]);

        $message = Message::sole();

        $this->assertSame(MessageModerationStatus::Approved, $message->moderation_status);
        $this->assertNotNull($message->moderated_at);

        $this->actingAs($student)
            ->get(route('community.show', $channel))
            ->assertInertia(fn (Assert $page) => $page->has('messages', 1)->where('messages.0.id', $message->id));
    }

    public function test_a_harmful_message_is_flagged_hidden_and_admins_are_notified(): void
    {
        Notification::fake();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'results' => [[
                    'flagged' => true,
                    'categories' => ['harassment' => true, 'sexual' => false, 'violence' => true],
                ]],
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)->post(route('community.messages.store', $channel), [
            'type' => 'text',
            'body' => '<p>Something inappropriate.</p>',
        ]);

        $message = Message::sole();

        $this->assertSame(MessageModerationStatus::Flagged, $message->moderation_status);
        $this->assertSame(['harassment', 'violence'], $message->moderation_flags);
        $this->assertNotNull($message->moderated_at);

        // Hidden from the other participant...
        $this->actingAs($student)
            ->get(route('community.show', $channel))
            ->assertInertia(fn (Assert $page) => $page->has('messages', 0));

        // ...but the sender still sees their own message.
        $this->actingAs($teacher)
            ->get(route('community.show', $channel))
            ->assertInertia(fn (Assert $page) => $page->has('messages', 1)->where('messages.0.id', $message->id));

        Notification::assertSentTo(
            $admin,
            MessageFlaggedForReview::class,
            fn (MessageFlaggedForReview $notification) => $notification->message->is($message)
        );
    }

    public function test_a_moderation_api_failure_hides_the_message_as_a_precaution(): void
    {
        Notification::fake();

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'service unavailable'], 500),
        ]);

        User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)->post(route('community.messages.store', $channel), [
            'type' => 'text',
            'body' => '<p>Hello</p>',
        ]);

        $message = Message::sole();

        $this->assertSame(MessageModerationStatus::Flagged, $message->moderation_status);

        $this->actingAs($student)
            ->get(route('community.show', $channel))
            ->assertInertia(fn (Assert $page) => $page->has('messages', 0));

        Notification::assertSentTo(User::role('admin')->get(), MessageFlaggedForReview::class);
    }

    public function test_moderation_is_skipped_and_message_approved_when_openai_is_not_configured(): void
    {
        config(['services.openai.key' => null]);

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)->post(route('community.messages.store', $channel), [
            'type' => 'text',
            'body' => '<p>Hello</p>',
        ]);

        $this->assertSame(MessageModerationStatus::Approved, Message::sole()->moderation_status);
    }

    public function test_admin_can_approve_a_flagged_message_making_it_visible(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'results' => [['flagged' => true, 'categories' => ['harassment' => true]]],
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = $this->directChannel($teacher, $student);

        $this->actingAs($teacher)->post(route('community.messages.store', $channel), [
            'type' => 'text',
            'body' => '<p>Flagged for testing.</p>',
        ]);

        $message = Message::sole();
        $this->assertSame(MessageModerationStatus::Flagged, $message->moderation_status);

        $message->approveModeration();

        $this->assertSame(MessageModerationStatus::Approved, $message->fresh()->moderation_status);

        $this->actingAs($student)
            ->get(route('community.show', $channel))
            ->assertInertia(fn (Assert $page) => $page->has('messages', 1)->where('messages.0.id', $message->id));

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.flagged-messages.index'))
            ->assertOk();
    }

    private function directChannel(User $a, User $b): Channel
    {
        $channel = Channel::factory()->create(['type' => ChannelType::Direct]);
        $channel->users()->attach([$a->id, $b->id]);

        return $channel;
    }
}
