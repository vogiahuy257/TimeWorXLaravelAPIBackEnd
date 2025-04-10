<?php

namespace App\Data\Factories;

use App\Data\NotificationData;
use App\Data\Contracts\NotificationDataInterface;

class NotificationDataFactory
{
    public static function make(
        string $userId,
        string $type,
        string $message,
        ?string $link = null
    ): NotificationDataInterface {
        // Có thể validate type ở đây nếu cần
        return new NotificationData($userId, $type, $message, $link);
    }
}
