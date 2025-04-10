<?php
namespace App\Services;

use App\Data\ProjectStatusUpdateData;
use App\Events\ProjectStatusUpdated;
use App\Models\Project;

class ProjectStatusBroadcastService
{
    public function broadcastToProjectMembers(Project $project): void
    {
        foreach ($project->users as $user) {
            broadcast(new ProjectStatusUpdated(
                new ProjectStatusUpdateData(
                    $project->id,
                    $project->status,
                    $user->id
                )
            ));
        }
    }
}
