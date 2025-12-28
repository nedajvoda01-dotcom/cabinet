<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Integration;

use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Handlers\CreateTaskHandler;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryTaskRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryPipelineStateRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator;
use Cabinet\Backend\Infrastructure\Persistence\PDO\ConnectionFactory;
use Cabinet\Backend\Infrastructure\Persistence\PDO\MigrationsRunner;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\SQLiteJobQueue;
use Cabinet\Backend\Tests\TestCase;
use Cabinet\Contracts\ErrorKind;
use Cabinet\Contracts\JobStatus;

final class JobQueueTest extends TestCase
{
    private function setupFreshDatabase(): \PDO
    {
        // Use a unique database file for tests
        $dbPath = '/tmp/test-jobqueue-' . uniqid() . '.db';
        putenv('DB_PATH=' . $dbPath);
        
        ConnectionFactory::reset();
        $pdo = ConnectionFactory::create();
        $migrations = new MigrationsRunner($pdo);
        $migrations->run();
        
        return $pdo;
    }

    public function testEnqueueCreatesJob(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $taskId = TaskId::fromString('task-123');

        $jobId = $jobQueue->enqueueAdvance($taskId);

        $this->assertTrue(!empty($jobId), 'Job ID should not be empty');
        
        // Verify job was created in database
        $stmt = $pdo->prepare('SELECT * FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row !== false, 'Job should exist in database');
        $this->assertTrue($row['task_id'] === 'task-123', 'Task ID should match');
        $this->assertTrue($row['kind'] === 'advance_pipeline', 'Kind should be advance_pipeline');
        $this->assertTrue($row['status'] === JobStatus::QUEUED->value, 'Status should be queued');
        $this->assertTrue((int)$row['attempt'] === 0, 'Initial attempt should be 0');
    }

    public function testClaimNextReturnsNullWhenNoJobs(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $claimedJob = $jobQueue->claimNext();

        $this->assertTrue($claimedJob === null, 'Should return null when no jobs available');
    }

    public function testClaimNextReturnsAndUpdatesJob(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $taskId = TaskId::fromString('task-456');
        $jobId = $jobQueue->enqueueAdvance($taskId);

        $claimedJob = $jobQueue->claimNext();

        $this->assertTrue($claimedJob !== null, 'Should return a claimed job');
        $this->assertTrue($claimedJob->jobId() === $jobId, 'Job ID should match');
        $this->assertTrue($claimedJob->taskId() === 'task-456', 'Task ID should match');
        $this->assertTrue($claimedJob->kind() === 'advance_pipeline', 'Kind should match');
        $this->assertTrue($claimedJob->attempt() === 1, 'Attempt should be incremented to 1');

        // Verify job status was updated to running
        $stmt = $pdo->prepare('SELECT status, attempt FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row['status'] === JobStatus::RUNNING->value, 'Status should be running');
        $this->assertTrue((int)$row['attempt'] === 1, 'Attempt should be 1');
    }

    public function testMarkSucceededUpdatesStatus(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $taskId = TaskId::fromString('task-789');
        $jobId = $jobQueue->enqueueAdvance($taskId);

        $claimedJob = $jobQueue->claimNext();
        $jobQueue->markSucceeded($claimedJob->jobId());

        // Verify status was updated
        $stmt = $pdo->prepare('SELECT status FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row['status'] === JobStatus::SUCCEEDED->value, 'Status should be succeeded');
    }

    public function testMarkFailedRetryable(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $taskId = TaskId::fromString('task-retry');
        $jobId = $jobQueue->enqueueAdvance($taskId);

        $claimedJob = $jobQueue->claimNext();
        $jobQueue->markFailed($claimedJob->jobId(), ErrorKind::INTEGRATION_UNAVAILABLE, true);

        // Verify job was requeued with backoff
        $stmt = $pdo->prepare('SELECT status, attempt, last_error_kind FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row['status'] === JobStatus::QUEUED->value, 'Status should be queued for retry');
        $this->assertTrue((int)$row['attempt'] === 1, 'Attempt should remain 1');
        $this->assertTrue($row['last_error_kind'] === 'integration_unavailable', 'Error kind should be stored');
    }

    public function testMarkFailedNonRetryable(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $taskId = TaskId::fromString('task-fail');
        $jobId = $jobQueue->enqueueAdvance($taskId);

        $claimedJob = $jobQueue->claimNext();
        $jobQueue->markFailed($claimedJob->jobId(), ErrorKind::VALIDATION_ERROR, false);

        // Verify job was moved to DLQ
        $stmt = $pdo->prepare('SELECT status, last_error_kind FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row['status'] === JobStatus::DEAD_LETTER->value, 'Status should be dead_letter');
        $this->assertTrue($row['last_error_kind'] === 'validation_error', 'Error kind should be stored');
    }

    public function testMaxAttemptsMovesToDlq(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $taskId = TaskId::fromString('task-max-retry');
        $jobId = $jobQueue->enqueueAdvance($taskId);

        // Attempt 1
        $claimedJob = $jobQueue->claimNext();
        $jobQueue->markFailed($claimedJob->jobId(), ErrorKind::INTEGRATION_UNAVAILABLE, true);

        // Manually set available_at to now to make it immediately claimable
        $now = (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z');
        $stmt = $pdo->prepare('UPDATE jobs SET available_at = :now WHERE job_id = :job_id');
        $stmt->execute(['now' => $now, 'job_id' => $jobId]);

        // Attempt 2
        $claimedJob = $jobQueue->claimNext();
        $this->assertTrue($claimedJob !== null, 'Should be able to claim job for attempt 2');
        $jobQueue->markFailed($claimedJob->jobId(), ErrorKind::INTEGRATION_UNAVAILABLE, true);

        // Manually set available_at to now again
        $stmt->execute(['now' => $now, 'job_id' => $jobId]);

        // Attempt 3 (max)
        $claimedJob = $jobQueue->claimNext();
        $this->assertTrue($claimedJob !== null, 'Should be able to claim job for attempt 3');
        $jobQueue->markFailed($claimedJob->jobId(), ErrorKind::INTEGRATION_UNAVAILABLE, true);

        // Verify job was moved to DLQ after max attempts
        $stmt = $pdo->prepare('SELECT status, attempt FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row['status'] === JobStatus::DEAD_LETTER->value, 'Status should be dead_letter after max attempts');
        $this->assertTrue((int)$row['attempt'] === 3, 'Attempt should be 3');
    }

    public function testMoveToDlq(): void
    {
        $pdo = $this->setupFreshDatabase();

        $jobQueue = new SQLiteJobQueue($pdo);
        $taskId = TaskId::fromString('task-dlq');
        $jobId = $jobQueue->enqueueAdvance($taskId);

        $jobQueue->moveToDlq($jobId, ErrorKind::INTERNAL_ERROR);

        // Verify job was moved to DLQ
        $stmt = $pdo->prepare('SELECT status, last_error_kind FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row['status'] === JobStatus::DEAD_LETTER->value, 'Status should be dead_letter');
        $this->assertTrue($row['last_error_kind'] === 'internal_error', 'Error kind should be stored');
    }
}
