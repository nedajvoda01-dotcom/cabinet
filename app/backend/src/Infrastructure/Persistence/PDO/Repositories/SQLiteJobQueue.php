<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories;

use Cabinet\Backend\Application\Ports\ClaimedJob;
use Cabinet\Backend\Application\Ports\JobQueue;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Contracts\ErrorKind;
use Cabinet\Contracts\JobStatus;
use PDO;

final class SQLiteJobQueue implements JobQueue
{
    private const MAX_ATTEMPTS = 3;
    private const KIND_ADVANCE_PIPELINE = 'advance_pipeline';
    private const BACKOFF_BASE_SECONDS = 10;

    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function enqueueAdvance(TaskId $taskId): string
    {
        $jobId = $this->generateJobId();
        $now = $this->now();
        
        $payload = json_encode(['task_id' => $taskId->toString()]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO jobs (job_id, task_id, kind, status, attempt, available_at, last_error_kind, payload_json, created_at, updated_at)
             VALUES (:job_id, :task_id, :kind, :status, :attempt, :available_at, :last_error_kind, :payload_json, :created_at, :updated_at)'
        );

        $stmt->execute([
            'job_id' => $jobId,
            'task_id' => $taskId->toString(),
            'kind' => self::KIND_ADVANCE_PIPELINE,
            'status' => JobStatus::QUEUED->value,
            'attempt' => 0,
            'available_at' => $now,
            'last_error_kind' => null,
            'payload_json' => $payload,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $jobId;
    }

    public function claimNext(): ?ClaimedJob
    {
        // Start transaction for atomic claim
        $this->pdo->beginTransaction();

        try {
            // Find next available job
            $stmt = $this->pdo->prepare(
                'SELECT job_id, task_id, kind, attempt 
                 FROM jobs 
                 WHERE status = :status AND available_at <= :now
                 ORDER BY available_at ASC, created_at ASC
                 LIMIT 1'
            );

            $stmt->execute([
                'status' => JobStatus::QUEUED->value,
                'now' => $this->now(),
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                $this->pdo->rollBack();
                return null;
            }

            // Update job to running status
            $updateStmt = $this->pdo->prepare(
                'UPDATE jobs 
                 SET status = :status, attempt = :attempt, updated_at = :updated_at
                 WHERE job_id = :job_id'
            );

            $newAttempt = $row['attempt'] + 1;

            $updateStmt->execute([
                'status' => JobStatus::RUNNING->value,
                'attempt' => $newAttempt,
                'updated_at' => $this->now(),
                'job_id' => $row['job_id'],
            ]);

            $this->pdo->commit();

            return new ClaimedJob(
                $row['job_id'],
                $row['task_id'],
                $row['kind'],
                $newAttempt
            );
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function markSucceeded(string $jobId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE jobs 
             SET status = :status, updated_at = :updated_at
             WHERE job_id = :job_id'
        );

        $stmt->execute([
            'status' => JobStatus::SUCCEEDED->value,
            'updated_at' => $this->now(),
            'job_id' => $jobId,
        ]);
    }

    public function markFailed(string $jobId, ErrorKind $errorKind, bool $retryable): void
    {
        // Fetch current attempt count
        $stmt = $this->pdo->prepare(
            'SELECT attempt FROM jobs WHERE job_id = :job_id'
        );
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return;
        }

        $attempt = (int)$row['attempt'];

        if (!$retryable || $attempt >= self::MAX_ATTEMPTS) {
            // Move to DLQ
            $this->moveToDlq($jobId, $errorKind);
            return;
        }

        // Schedule for retry with linear backoff (as per spec: 10 * attempt)
        $backoffSeconds = self::BACKOFF_BASE_SECONDS * $attempt;
        $availableAt = $this->addSeconds($this->now(), $backoffSeconds);

        $updateStmt = $this->pdo->prepare(
            'UPDATE jobs 
             SET status = :status, available_at = :available_at, last_error_kind = :last_error_kind, updated_at = :updated_at
             WHERE job_id = :job_id'
        );

        $updateStmt->execute([
            'status' => JobStatus::QUEUED->value,
            'available_at' => $availableAt,
            'last_error_kind' => $errorKind->value,
            'updated_at' => $this->now(),
            'job_id' => $jobId,
        ]);
    }

    public function moveToDlq(string $jobId, ErrorKind $errorKind): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE jobs 
             SET status = :status, last_error_kind = :last_error_kind, updated_at = :updated_at
             WHERE job_id = :job_id'
        );

        $stmt->execute([
            'status' => JobStatus::DEAD_LETTER->value,
            'last_error_kind' => $errorKind->value,
            'updated_at' => $this->now(),
            'job_id' => $jobId,
        ]);
    }

    private function generateJobId(): string
    {
        return sprintf('job-%s', uniqid('', true));
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z');
    }

    private function addSeconds(string $timestamp, int $seconds): string
    {
        $dt = new \DateTimeImmutable($timestamp);
        return $dt->add(new \DateInterval(sprintf('PT%dS', $seconds)))->format('Y-m-d\TH:i:s.u\Z');
    }
}
