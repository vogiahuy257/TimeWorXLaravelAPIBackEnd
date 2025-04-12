<?php
namespace App\Services;

use App\Data\ProjectStatusUpdateData;
use Illuminate\Support\Collection;
use App\Events\ProjectStatusUpdated;
use App\Models\Project;
use App\Models\Task;

class ProjectStatusBroadcastService
{
    protected Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Broadcast the project status update to all users in the project.
     *
     * @return void
     */
    public function broadcastToProjectMembers(): void
    {
        $managers = $this->project->users()
                              ->wherePivot('is_project_manager', true)
                              ->get();
        
        $userIds = collect($managers)->pluck('id');
        $pivotUserIds = collect($managers)->pluck('pivot.user_id');

        $mergedIds = $userIds->merge($pivotUserIds)->unique()->values()->toArray();

        foreach ($mergedIds as $userId) {
            broadcast(new ProjectStatusUpdated(
                new ProjectStatusUpdateData(
                    $this->project->project_id,
                    $this->project->project_status,
                    $userId
                )
            ))->toOthers();
        }
    }

    /**
     * Update the project status based on its tasks, and broadcast if changed.
     *
     * @return bool
     */
    public function updateAndSendProjectStatus($newStatus): bool
    {
        $tasks = Task::where('project_id', $this->project->project_id)->pluck('status_key');

        if ($tasks->contains('verify')) {
            $newStatus = 'verify';
        } elseif ($tasks->contains('in-progress')) {
            $newStatus = 'in-progress';
        } elseif ($tasks->contains('to-do')) {
            $newStatus = 'to-do';
        } elseif ($tasks->contains('done')) {
            $newStatus = 'done';
        }
        else
        {
            $newStatus = "to-do";
        }

        // Only update and broadcast if the status has changed
        if ($this->project->project_status !== $newStatus) {
            $this->project->project_status = $newStatus;
            $this->project->save();

            $this->broadcastToProjectMembers();
            return true;
        }

        return false;
    }
}
