<?php
// backend/src/Queues/QueueService.php

namespace App\Queues;

use Backend\Logger\LoggerInterface;

final class QueueService
{
    public function __construct(
        private QueueRepository $repo,
        private DlqRepository $dlq,
        private RetryPolicy $policy,
        private LoggerInterface $log
    ) {}

    // ---- enqueue helpers for Modules ----

    public function enqueuePhotos(int $cardId, array $payload = []): QueueJob
    {
        $job = $this->repo->enqueue(QueueTypes::PHOTOS, 'card', $cardId, $payload);

        $this->log->info("job enqueued", [
            'correlation_id' => $payload['correlation_id'] ?? null,
            'job_id' => $job->id,
            'type' => QueueTypes::PHOTOS,
            'entity' => 'card',
            'entity_id' => $cardId,
            'card_id' => $cardId,
            'payload' => $payload,
        ]);

        return $job;
    }

    public function enqueueExport(int $exportId, array $payload = []): QueueJob
    {
        $job = $this->repo->enqueue(QueueTypes::EXPORT, 'export', $exportId, $payload);

        $this->log->info("job enqueued", [
            'correlation_id' => $payload['correlation_id'] ?? null,
            'job_id' => $job->id,
            'type' => QueueTypes::EXPORT,
            'entity' => 'export',
            'entity_id' => $exportId,
            'card_id' => null,
            'payload' => $payload,
        ]);

        return $job;
    }

    public function enqueuePublish(int $cardId, array $payload = []): QueueJob
    {
        $job = $this->repo->enqueue(QueueTypes::PUBLISH, 'card', $cardId, $payload);

        $this->log->info("job enqueued", [
            'correlation_id' => $payload['correlation_id'] ?? null,
            'job_id' => $job->id,
            'type' => QueueTypes::PUBLISH,
            'entity' => 'card',
            'entity_id' => $cardId,
            'card_id' => $cardId,
            'payload' => $payload,
        ]);

        return $job;
    }

    public function enqueueParser(int $payloadId, array $payload = []): QueueJob
    {
        $job = $this->repo->enqueue(QueueTypes::PARSER, 'parser_payload', $payloadId, $payload);

        $this->log->info("job enqueued", [
            'correlation_id' => $payload['correlation_id'] ?? null,
            'job_id' => $job->id,
            'type' => QueueTypes::PARSER,
            'entity' => 'parser_payload',
            'entity_id' => $payloadId,
            'card_id' => null,
            'payload' => $payload,
        ]);

        return $job;
    }

    public function enqueueRobotStatus(int $publishJobId, array $payload = []): QueueJob
    {
        $job = $this->repo->enqueue(QueueTypes::ROBOT_STATUS, 'publish_job', $publishJobId, $payload);

        $this->log->info("job enqueued", [
            'correlation_id' => $payload['correlation_id'] ?? null,
            'job_id' => $job->id,
            'type' => QueueTypes::ROBOT_STATUS,
            'entity' => 'publish_job',
            'entity_id' => $publishJobId,
            'card_id' => null,
            'payload' => $payload,
        ]);

        return $job;
    }

    // ---- worker API ----

    public function fetchNext(string $type, string $workerId): ?QueueJob
    {
        return $this->repo->fetchNext($type, $workerId);
    }

    public function handleSuccess(QueueJob $job): void
    {
        $this->repo->markDone($job->id);

        $this->log->info("job success", [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'job_id' => $job->id,
            'type' => $job->type,
            'attempts' => $job->attempts,
            'entity' => $job->entity,
            'entity_id' => $job->entityId,
            'card_id' => $job->entity === 'card' ? $job->entityId : null,
        ]);
    }

    /**
     * @param array $error {code?, message, meta?, fatal?:bool}
     */
    public function handleFailure(QueueJob $job, array $error): void
    {
        $fatal = (bool)($error['fatal'] ?? false);
        $attempts = $job->attempts + 1;

        // общий лог ошибки
        $this->log->error("job failed", [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'job_id' => $job->id,
            'type' => $job->type,
            'attempts' => $attempts,
            'entity' => $job->entity,
            'entity_id' => $job->entityId,
            'card_id' => $job->entity === 'card' ? $job->entityId : null,
            'last_error' => $error,
        ]);

        // fatal → сразу DLQ
        if ($fatal) {
            $job->attempts = $attempts;
            $job->lastError = $error;

            $this->repo->markDead($job->id, $attempts, $error);
            $this->dlq->put($job);

            $this->log->error("job moved to dlq", [
                'correlation_id' => $job->payload['correlation_id'] ?? null,
                'job_id' => $job->id,
                'type' => $job->type,
                'attempts' => $attempts,
                'entity' => $job->entity,
                'entity_id' => $job->entityId,
                'card_id' => $job->entity === 'card' ? $job->entityId : null,
                'last_error' => $error,
                'reason' => 'fatal',
            ]);

            return;
        }

        if ($this->policy->shouldRetry($attempts)) {
            $nextRetryAt = $this->policy->nextRetryAt($attempts);
            $this->repo->markRetrying($job->id, $attempts, $nextRetryAt, $error);

            // полезный лог про retry
            $this->log->warn("job scheduled for retry", [
                'correlation_id' => $job->payload['correlation_id'] ?? null,
                'job_id' => $job->id,
                'type' => $job->type,
                'attempts' => $attempts,
                'next_retry_at' => $nextRetryAt?->format('c') ?? null,
                'card_id' => $job->entity === 'card' ? $job->entityId : null,
            ]);
        } else {
            // attempts exhausted → DLQ
            $job->attempts = $attempts;
            $job->lastError = $error;

            $this->repo->markDead($job->id, $attempts, $error);
            $this->dlq->put($job);

            $this->log->error("job moved to dlq", [
                'correlation_id' => $job->payload['correlation_id'] ?? null,
                'job_id' => $job->id,
                'type' => $job->type,
                'attempts' => $attempts,
                'entity' => $job->entity,
                'entity_id' => $job->entityId,
                'card_id' => $job->entity === 'card' ? $job->entityId : null,
                'last_error' => $error,
                'reason' => 'attempts_exhausted',
            ]);
        }
    }
}
