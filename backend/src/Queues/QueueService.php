<?php
// backend/src/Queues/QueueService.php

namespace App\Queues;

final class QueueService
{
    public function __construct(
        private QueueRepository $repo,
        private DlqRepository $dlq,
        private RetryPolicy $policy
    ) {}

    // ---- enqueue helpers for Modules ----

    public function enqueuePhotos(int $cardId, array $payload = []): QueueJob
    {
        return $this->repo->enqueue(QueueTypes::PHOTOS, 'card', $cardId, $payload);
    }

    public function enqueueExport(int $exportId, array $payload = []): QueueJob
    {
        return $this->repo->enqueue(QueueTypes::EXPORT, 'export', $exportId, $payload);
    }

    public function enqueuePublish(int $cardId, array $payload = []): QueueJob
    {
        return $this->repo->enqueue(QueueTypes::PUBLISH, 'card', $cardId, $payload);
    }

    public function enqueueParser(int $payloadId, array $payload = []): QueueJob
    {
        return $this->repo->enqueue(QueueTypes::PARSER, 'parser_payload', $payloadId, $payload);
    }

    public function enqueueRobotStatus(int $publishJobId, array $payload = []): QueueJob
    {
        return $this->repo->enqueue(QueueTypes::ROBOT_STATUS, 'publish_job', $publishJobId, $payload);
    }

    // ---- worker API ----

    public function fetchNext(string $type, string $workerId): ?QueueJob
    {
        return $this->repo->fetchNext($type, $workerId);
    }

    public function handleSuccess(QueueJob $job): void
    {
        $this->repo->markDone($job->id);
    }

    /**
     * @param array $error {code?, message, meta?, fatal?:bool}
     */
    public function handleFailure(QueueJob $job, array $error): void
    {
        $fatal = (bool)($error['fatal'] ?? false);
        $attempts = $job->attempts + 1;

        // fatal → сразу DLQ
        if ($fatal) {
            $job->attempts = $attempts;
            $job->lastError = $error;
            $this->repo->markDead($job->id, $attempts, $error);
            $this->dlq->put($job);
            return;
        }

        if ($this->policy->shouldRetry($attempts)) {
            $nextRetryAt = $this->policy->nextRetryAt($attempts);
            $this->repo->markRetrying($job->id, $attempts, $nextRetryAt, $error);
        } else {
            // attempts exhausted → DLQ
            $job->attempts = $attempts;
            $job->lastError = $error;
            $this->repo->markDead($job->id, $attempts, $error);
            $this->dlq->put($job);
        }
    }
}
