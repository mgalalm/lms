<?php

namespace App\Http\Controllers\Student;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\LeaderboardEntryResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementsController extends Controller
{
    /**
     * The full Coins/Badges/Leaderboard view — the "View All" destination
     * from the dashboard's compact Earnings & Gamification widgets.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $leaderboard = User::leaderboard(UserRole::Student->value)->limit(50)->get()
            ->map(fn (User $entry, int $index) => new LeaderboardEntryResource($entry, $index + 1, $user->id));

        return Inertia::render('student/Achievements', [
            'points' => $user->totalPoints(),
            'badges' => BadgeResource::collection($user->badges()->orderByDesc('badge_user.awarded_at')->get()),
            'leaderboard' => $leaderboard,
        ]);
    }
}
