<?php
namespace App\Services;

use App\Events\TaskStatusUpdated;
use App\Events\TaskOnProjectStatusUpdated;
use App\Models\Task;

class TaskStatusBroadcastService
{
    public function sendStatusUpdate(Task $task, string $statusKey): void
    {
        \Log::info('Broadcasting task status update', [
            'task_id' => $task->task_id,
            'status_key' => $statusKey,
        ]);
        broadcast(new TaskStatusUpdated($task->task_id,$statusKey))->toOthers();
        broadcast(new TaskOnProjectStatusUpdated($task->task_id, $statusKey, $task->project_id))->toOthers();
    }
}
