<?php

use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('channel.{channel}', function (User $user, Channel $channel) {
    return $user->can('view', $channel);
});
