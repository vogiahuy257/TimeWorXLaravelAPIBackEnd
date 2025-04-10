<?php

namespace App\Data;

use App\Data\Contracts\NotificationDataInterface;

class NotificationData implements NotificationDataInterface
{
    public function __construct(
        protected string $userId,
        protected string $type,
        protected string $message,
        protected ?string $link = null
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'notification_type' => $this->type,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }
}
