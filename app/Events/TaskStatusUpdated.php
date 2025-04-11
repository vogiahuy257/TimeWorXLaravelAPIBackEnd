<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public string $taskId;
    public string $statusKey;

    public function __construct(string $taskId, string $statusKey)
    {
        $this->taskId = $taskId;
        $this->statusKey = $statusKey;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('task');
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
        return 'TaskStatusUpdated';
    }
}
