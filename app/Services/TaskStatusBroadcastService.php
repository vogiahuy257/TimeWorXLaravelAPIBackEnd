<?php
namespace App\Services;

use App\Events\TaskStatusUpdated;
use App\Events\TaskOnProjectStatusUpdated;
use App\Models\Task;

class TaskStatusBroadcastService
{
    public function sendStatusUpdate(Task $task, string $statusKey): void
    {
        broadcast(new TaskStatusUpdated($task->task_id,$statusKey))->toOthers();
        broadcast(new TaskOnProjectStatusUpdated($task->task_id, $statusKey, $task->project_id))->toOthers();
    }
}
