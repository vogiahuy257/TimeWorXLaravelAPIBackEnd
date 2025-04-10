<?php

namespace App\Data;

use App\Data\Contracts\ProjectStatusUpdateDataInterface;

class ProjectStatusUpdateData implements ProjectStatusUpdateDataInterface
{
    protected int $projectId;
    protected string $status;
    protected string $userId;

    public function __construct(int $projectId, string $status, string $userId)
    {
        $this->projectId = $projectId;
        $this->status = $status;
        $this->userId = $userId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['project_id'],
            (string) $data['project_status'],
            (string) $data['user_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'project_status' => $this->status,
            'user_id' => $this->userId,
        ];
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
