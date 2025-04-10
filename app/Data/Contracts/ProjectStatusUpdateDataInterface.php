<?php

namespace App\Data\Contracts;

interface ProjectStatusUpdateDataInterface
{
    public function getProjectId(): int;

    public function getStatus(): string;

    public function getUserId(): string;

    public function toArray(): array;
}
