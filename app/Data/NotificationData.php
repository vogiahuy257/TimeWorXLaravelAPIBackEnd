<?php

namespace App\Data;

use App\Data\Contracts\NotificationDataInterface;
use Illuminate\Support\Carbon; // thêm use nếu cần

class NotificationData implements NotificationDataInterface
{
    public function __construct(
        protected string $userId,
        protected string $type,
        protected string $message,
        protected ?string $link = null,
        protected ?string $notificationDate = null // thêm để lưu ngày
    ) {
        $this->notificationDate = now(); // gán nếu chưa có
    }

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

    public function getNotificationDate(): string
    {
        return $this->notificationDate;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'notification_type' => $this->type,
            'message' => $this->message,
            'link' => $this->link,
            'notification_date' => $this->notificationDate,
        ];
    }
}
