<?php
declare(strict_types=1);

namespace Modules\Robot;

use Throwable;

/**
 * Interfaces (adapters) are assumed to exist in project:
 * - DolphinAdapterInterface: open session/profile, upload photos, etc.
 * - AvitoAdapterInterface: publish ad, get status.
 *
 * RobotService never depends on concrete implementations.
 */
interface DolphinAdapterInterface {
    public function startSession(array $options = []): array; // returns ['session_id'=>..., ...]
    public function closeSession(array $sessionRef): void;
}

interface AvitoAdapterInterface {
    public function publish(array $cardSnapshot, array $sessionRef, array $options = []): array;
    public function getPublishStatus(array $externalRef, array $sessionRef): array;
}

final class RobotService
{
    public function __construct(
        private RobotModel $model,
        private DolphinAdapterInterface $dolphin,
        private AvitoAdapterInterface $avito,
        private int $maxAttempts = 5
    ) {}

    /**
     * Called by PublishWorker (through RobotJobs).
     * Creates RobotRun and attempts to publish immediately in worker.
     */
    public function publishCard(int $cardId, int $publishJobId, array $cardSnapshot, array $options = []): array
    {
        $idempotencyKey = $this->makeIdempotencyKey($cardId, $publishJobId);

        // idempotency: if last run already succeeded or in progress, return it
        $latest = $this->model->findLatestByIdempotencyKey($idempotencyKey);
        if ($latest && in_array($latest['status'], ['processing','external_wait','success'], true)) {
            return $latest;
        }

        $runId = $this->model->createRun([
            'card_id' => $cardId,
            'publish_job_id' => $publishJobId,
            'idempotency_key' => $idempotencyKey,
            'status' => 'processing',
            'attempt' => $latest ? ((int)$latest['attempt'] + 1) : 1,
            'payload' => [
                'card_snapshot' => $cardSnapshot,
                'options' => $options,
            ],
        ]);

        $run = $this->model->findById($runId);

        try {
            $sessionRef = $this->dolphin->startSession($options['dolphin'] ?? []);

            $publishRes = $this->avito->publish(
                $cardSnapshot,
                $sessionRef,
                $options['avito'] ?? []
            );

            // publishRes should contain external refs and maybe immediate status
            $externalRef = $publishRes['external_ref'] ?? $publishRes;

            $status = $publishRes['status'] ?? 'external_wait';
            if ($status === 'published') {
                $this->model->updateRun($runId, [
                    'status' => 'success',
                    'external_ref' => $externalRef,
                    'last_error' => null,
                ]);
            } else {
                // Still in progress on Avito side
                $this->model->updateRun($runId, [
                    'status' => 'external_wait',
                    'external_ref' => $externalRef,
                ]);
            }

            $this->dolphin->closeSession($sessionRef);
        } catch (Throwable $e) {
            $attempt = (int)($run['attempt'] ?? 1);

            $isFatal = $this->isFatalError($e);
            $nextStatus = $isFatal ? 'failed_fatal' : 'failed_retry';

            $this->model->updateRun($runId, [
                'status' => $nextStatus,
                'last_error' => [
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                ],
            ]);

            if (!$isFatal && $attempt < $this->maxAttempts) {
                // allow retry by queue policy (handled by Queues subsystem)
            }
        }

        return $this->model->findById($runId) ?? [];
    }

    public function getRunStatus(int $runId): array
    {
        $run = $this->model->findById($runId);
        if (!$run) {
            throw new \RuntimeException("RobotRun not found: {$runId}");
        }
        return $run;
    }

    /**
     * Called by robot_status worker periodically.
     * Pulls statuses from Avito and updates runs.
     */
    public function syncStatuses(array $filter = []): array
    {
        $runs = $this->model->getRunsForSync($filter['limit'] ?? 100);
        $updated = [];

        foreach ($runs as $run) {
            try {
                $externalRef = json_decode($run['external_ref_json'] ?? '[]', true) ?: [];
                if (!$externalRef) continue;

                // We may need a fresh dolphin session for status check
                $sessionRef = $this->dolphin->startSession($filter['dolphin'] ?? []);

                $statusRes = $this->avito->getPublishStatus($externalRef, $sessionRef);
                $extStatus = $statusRes['status'] ?? null;

                if ($extStatus === 'published') {
                    $this->model->updateRun((int)$run['id'], [
                        'status' => 'success',
                        'last_error' => null,
                        'external_ref' => $externalRef,
                    ]);
                    $updated[] = (int)$run['id'];
                } elseif ($extStatus === 'failed') {
                    $this->model->updateRun((int)$run['id'], [
                        'status' => 'failed_retry',
                        'last_error' => $statusRes['error'] ?? ['message' => 'Publish failed'],
                    ]);
                    $updated[] = (int)$run['id'];
                } else {
                    // keep waiting
                    $this->model->updateRun((int)$run['id'], [
                        'status' => 'external_wait',
                    ]);
                }

                $this->dolphin->closeSession($sessionRef);
            } catch (Throwable $e) {
                // non-fatal for sync; leave run for next sync
                $this->model->updateRun((int)$run['id'], [
                    'last_error' => [
                        'message' => $e->getMessage(),
                        'class' => get_class($e),
                        'stage' => 'sync',
                    ],
                ]);
            }
        }

        return ['updated_run_ids' => $updated];
    }

    public function retryRun(int $runId): array
    {
        $run = $this->getRunStatus($runId);
        $payload = json_decode($run['payload_json'] ?? '[]', true) ?: [];
        $cardSnapshot = $payload['card_snapshot'] ?? [];
        $options = $payload['options'] ?? [];

        return $this->publishCard(
            (int)$run['card_id'],
            (int)$run['publish_job_id'],
            $cardSnapshot,
            $options
        );
    }

    private function makeIdempotencyKey(int $cardId, int $publishJobId): string
    {
        return "robot_publish:card={$cardId}:publish_job={$publishJobId}";
    }

    private function isFatalError(Throwable $e): bool
    {
        $msg = mb_strtolower($e->getMessage());
        // Heuristic: contract errors, bans, invalid data => fatal
        return str_contains($msg, 'contract')
            || str_contains($msg, 'invalid')
            || str_contains($msg, 'ban')
            || str_contains($msg, 'forbidden');
    }
}
