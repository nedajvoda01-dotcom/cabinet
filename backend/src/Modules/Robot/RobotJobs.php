<?php
declare(strict_types=1);

namespace Modules\Robot;

/**
 * Base interface for queue jobs in project.
 * Real Queues subsystem should call ->handle().
 */
interface QueueJobInterface {
    public function handle(RobotService $service): void;
    public function payload(): array;
    public function idempotencyKey(): string;
}

/**
 * Job that performs actual publication via RobotService.
 * Enqueued by PublishModule.
 */
final class RobotPublishJob implements QueueJobInterface
{
    public function __construct(private array $payload) {
        $this->payload = RobotSchemas::validatePublishPayload($payload);
    }

    public function handle(RobotService $service): void
    {
        $service->publishCard(
            $this->payload['card_id'],
            $this->payload['publish_job_id'],
            $this->payload['card_snapshot'],
            $this->payload['options'] ?? [],
        );
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function idempotencyKey(): string
    {
        return "RobotPublishJob:" . $this->payload['card_id'] . ":" . $this->payload['publish_job_id'];
    }
}

/**
 * Periodic job to sync statuses from Avito side.
 * Enqueued by scheduler / Admin module.
 */
final class RobotStatusSyncJob implements QueueJobInterface
{
    public function __construct(private array $payload = []) {
        $this->payload = RobotSchemas::validateSyncPayload($payload);
    }

    public function handle(RobotService $service): void
    {
        $service->syncStatuses($this->payload['filter'] ?? []);
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function idempotencyKey(): string
    {
        return "RobotStatusSyncJob";
    }
}
