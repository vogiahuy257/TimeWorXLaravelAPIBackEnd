<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Data\Contracts\ProjectStatusUpdateDataInterface;

class ProjectStatusUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(ProjectStatusUpdateDataInterface $data)
    {
        $this->payload = $data->toArray();
    }


    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->payload['user_id']}");
    }

    public function broadcastAs(): string
    {
        return 'project.status.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
