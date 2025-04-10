<?php

namespace App\Events;

use App\Data\Contracts\NotificationDataInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
class NotificationReceived implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(NotificationDataInterface $data)
    {
        $this->payload = $data->toArray();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("notification.{$this->payload['user_id']}");
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
