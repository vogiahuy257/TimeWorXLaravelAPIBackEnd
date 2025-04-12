<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
class TaskOnProjectStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public string $taskId;
    public string $projectId;
    public string $statusKey;

    public function __construct(string $taskId, string $statusKey,string $projectId)
    {
        $this->taskId = $taskId;
        $this->statusKey = $statusKey;
        $this->projectId = $projectId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("project.{$this->projectId}");
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'status_key' => $this->statusKey,
        ];
    }

    public function broadcastAs(): string
    {
        return 'TaskOnProjectStatusUpdated';
    }
}
