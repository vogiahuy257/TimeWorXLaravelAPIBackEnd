<?php

namespace App\Data\Contracts;

interface NotificationDataInterface
{
    public function getUserId(): string;

    public function getType(): string;

    public function getMessage(): string;

    public function getLink(): ?string;

    public function getNotificationDate(): string;

    public function toArray(): array;
}

