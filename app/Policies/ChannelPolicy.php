<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;

class ChannelPolicy
{
    /**
     * Determine whether the user can view the channel (and post messages in it).
     */
    public function view(User $user, Channel $channel): bool
    {
        return $channel->users()->whereKey($user->id)->exists();
    }
}
