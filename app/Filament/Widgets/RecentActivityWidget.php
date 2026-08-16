<?php

namespace App\Filament\Widgets;

use App\Enums\MessageModerationStatus;
use App\Enums\SessionStatus;
use App\Filament\Resources\FlaggedMessages\FlaggedMessageResource;
use App\Models\ClassSession;
use App\Models\Message;
use App\Models\StudentProfile;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecentActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-activity-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    private const PER_TYPE_LIMIT = 10;

    private const FEED_LIMIT = 10;

    /**
     * Merges four independent event streams (enrollments, completed
     * sessions, badge awards, flags) into a single feed sorted by
     * recency. Each stream is capped before merging so one very active
     * type can't crowd out the others before the final sort/take.
     *
     * @return Collection<int, array{type: string, description: string, timestamp: ?Carbon, url: ?string}>
     */
    public function getActivity(): Collection
    {
        return $this->enrollments()
            ->concat($this->completedSessions())
            ->concat($this->badgesAwarded())
            ->concat($this->flags())
            ->filter(fn (array $event) => $event['timestamp'] !== null)
            ->sortByDesc('timestamp')
            ->take(self::FEED_LIMIT)
            ->values();
    }

    /**
     * @return Collection<int, array{type: string, description: string, timestamp: ?Carbon, url: ?string}>
     */
    private function enrollments(): Collection
    {
        return StudentProfile::with('user')
            ->latest()
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (StudentProfile $profile) => [
                'type' => 'enrollment',
                'description' => ($profile->user?->name ?? 'A student').' enrolled',
                'timestamp' => $profile->created_at,
                'url' => null,
            ]);
    }

    /**
     * @return Collection<int, array{type: string, description: string, timestamp: ?Carbon, url: ?string}>
     */
    private function completedSessions(): Collection
    {
        return ClassSession::with(['teacher', 'student'])
            ->where('status', SessionStatus::Completed)
            ->latest('ended_at')
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (ClassSession $session) => [
                'type' => 'session_completed',
                'description' => ($session->teacher?->name ?? 'A teacher').' completed a session with '
                    .($session->student?->name ?? 'a student'),
                'timestamp' => $session->ended_at ?? $session->updated_at,
                'url' => null,
            ]);
    }

    /**
     * @return Collection<int, array{type: string, description: string, timestamp: ?Carbon, url: ?string}>
     */
    private function badgesAwarded(): Collection
    {
        return DB::table('badge_user')
            ->join('users', 'users.id', '=', 'badge_user.user_id')
            ->join('badges', 'badges.id', '=', 'badge_user.badge_id')
            ->whereNotNull('badge_user.awarded_at')
            ->orderByDesc('badge_user.awarded_at')
            ->limit(self::PER_TYPE_LIMIT)
            ->get(['users.name as user_name', 'badges.name as badge_name', 'badge_user.awarded_at'])
            ->map(fn ($row) => [
                'type' => 'badge_awarded',
                'description' => $row->user_name.' earned the "'.$row->badge_name.'" badge',
                'timestamp' => Carbon::parse($row->awarded_at),
                'url' => null,
            ]);
    }

    /**
     * @return Collection<int, array{type: string, description: string, timestamp: ?Carbon, url: ?string}>
     */
    private function flags(): Collection
    {
        return Message::with('user')
            ->where('moderation_status', MessageModerationStatus::Flagged)
            ->latest('moderated_at')
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (Message $message) => [
                'type' => 'flag',
                'description' => 'Message from '.($message->user?->name ?? 'a user').' flagged for review',
                'timestamp' => $message->moderated_at,
                'url' => FlaggedMessageResource::getUrl('view', ['record' => $message]),
            ]);
    }
}
