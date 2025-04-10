<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Project;

Broadcast::channel('notification.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
