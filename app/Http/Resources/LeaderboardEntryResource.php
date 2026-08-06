<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a User (with `total_points` set via User::scopeLeaderboard()) into
 * a ranked leaderboard row.
 */
class LeaderboardEntryResource extends JsonResource
{
    public function __construct(
        User $resource,
        private readonly int $rank,
        private readonly int $viewerId,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'id' => $this->id,
            'name' => $this->name,
            'points' => (int) ($this->total_points ?? 0),
            'is_current_user' => $this->id === $this->viewerId,
        ];
    }
}
