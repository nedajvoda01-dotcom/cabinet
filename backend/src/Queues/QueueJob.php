<?php
// backend/src/Queues/QueueJob.php

namespace App\Queues;

final class QueueJob
{
    public int $id;
    public string $type;        // photos|export|publish|parser|robot_status
    public string $entity;      // card|photo|export|publish_job
    public int $entityId;
    public array $payload;

    public int $attempts = 0;
    public ?string $nextRetryAt = null; // ISO string
    public string $status = 'queued';   // queued|processing|retrying|done|dead
    public ?array $lastError = null;    // {code?, message?, meta?}

    public ?string $lockedAt = null;
    public ?string $lockedBy = null;

    public string $createdAt;
    public string $updatedAt;

    public static function fromRow(array $row): self
    {
        $j = new self();
        $j->id         = (int)$row['id'];
        $j->type       = (string)$row['type'];
        $j->entity     = (string)$row['entity'];
        $j->entityId   = (int)$row['entity_id'];
        $j->payload    = json_decode($row['payload_json'] ?? '{}', true) ?: [];

        $j->attempts   = (int)($row['attempts'] ?? 0);
        $j->nextRetryAt= $row['next_retry_at'] ? (string)$row['next_retry_at'] : null;
        $j->status     = (string)($row['status'] ?? 'queued');
        $j->lastError  = $row['last_error_json'] ? (json_decode($row['last_error_json'], true) ?: null) : null;

        $j->lockedAt   = $row['locked_at'] ? (string)$row['locked_at'] : null;
        $j->lockedBy   = $row['locked_by'] ? (string)$row['locked_by'] : null;

        $j->createdAt  = (string)$row['created_at'];
        $j->updatedAt  = (string)$row['updated_at'];

        return $j;
    }

    public function entityRef(): array
    {
        return ['entity' => $this->entity, 'id' => $this->entityId];
    }
}
