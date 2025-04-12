<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Project;

Broadcast::channel('notification.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    $project = Project::find($projectId);
    if (!$project) return false;

    return
        $project->project_manager_id === $user->user_id ||
        $project->users()->where('user_id', $user->user_id)->exists(); // kiểm tra nếu là thành viên
});

Broadcast::channel('task', function ($user) {
    return $user !== null;
});
