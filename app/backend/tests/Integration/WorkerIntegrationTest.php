<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Integration;

use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Handlers\CreateTaskHandler;
use Cabinet\Backend\Application\Handlers\TickTaskHandler;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoParserAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoPhotosAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoPublisherAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoExportAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoCleanupAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Registry\IntegrationRegistry;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryTaskRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryPipelineStateRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\NoOpUnitOfWork;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator;
use Cabinet\Backend\Infrastructure\Persistence\PDO\ConnectionFactory;
use Cabinet\Backend\Infrastructure\Persistence\PDO\MigrationsRunner;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\TaskOutputsRepository;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\SQLiteJobQueue;
use Cabinet\Backend\Tests\TestCase;
use Cabinet\Contracts\JobStatus;

final class WorkerIntegrationTest extends TestCase
{
    private function setupFreshDatabase(): \PDO
    {
        $dbPath = '/tmp/test-worker-' . uniqid() . '.db';
        putenv('DB_PATH=' . $dbPath);
        
        ConnectionFactory::reset();
        $pdo = ConnectionFactory::create();
        $migrations = new MigrationsRunner($pdo);
        $migrations->run();
        
        return $pdo;
    }

    public function testWorkerProcessesEnqueuedJob(): void
    {
        $pdo = $this->setupFreshDatabase();

        // Setup repositories
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();
        $jobQueue = new SQLiteJobQueue($pdo);

        // Setup integrations
        $registry = new IntegrationRegistry(
            new DemoParserAdapter(),
            new DemoPhotosAdapter(),
            new DemoPublisherAdapter(),
            new DemoExportAdapter(),
            new DemoCleanupAdapter()
        );

        // Create a task
        $idGenerator = new UuidIdGenerator();
        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAuditLogger());
        $createCommand = new CreateTaskCommand('actor-123', 'idem-key-worker-1');
        $createResult = $createHandler->handle($createCommand);
        
        $this->assertTrue($createResult->isSuccess(), 'Task creation should succeed');
        $taskIdString = $createResult->value();
        $taskId = TaskId::fromString($taskIdString);

        // Enqueue the job
        $jobId = $jobQueue->enqueueAdvance($taskId);
        $this->assertTrue(!empty($jobId), 'Job should be enqueued');

        // Simulate worker: claim and process job
        $tickHandler = new TickTaskHandler($taskRepo, $pipelineRepo, $outputRepo, $registry, $unitOfWork, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAuditLogger(), new \Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator(), new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryMetricsEmitter());
        
        $claimedJob = $jobQueue->claimNext();
        $this->assertTrue($claimedJob !== null, 'Worker should claim a job');
        $this->assertTrue($claimedJob->taskId() === $taskIdString, 'Task ID should match');

        // Execute the tick command
        $command = new \Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand($claimedJob->taskId());
        $result = $tickHandler->handle($command);

        $this->assertTrue($result->isSuccess(), 'Tick should succeed');
        $this->assertTrue($result->value()['status'] === 'advanced', 'Should advance pipeline');
        $this->assertTrue($result->value()['completed_stage'] === 'parse', 'Should complete parse stage');

        // Mark job as succeeded
        $jobQueue->markSucceeded($claimedJob->jobId());

        // Verify job status
        $stmt = $pdo->prepare('SELECT status FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertTrue($row['status'] === JobStatus::SUCCEEDED->value, 'Job should be marked as succeeded');

        // Verify output was written
        $outputs = $outputRepo->read($taskId);
        $this->assertTrue(isset($outputs['parse']), 'Parse output should exist');
    }

    public function testWorkerProcessesMultipleStages(): void
    {
        $pdo = $this->setupFreshDatabase();

        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();
        $jobQueue = new SQLiteJobQueue($pdo);

        $registry = new IntegrationRegistry(
            new DemoParserAdapter(),
            new DemoPhotosAdapter(),
            new DemoPublisherAdapter(),
            new DemoExportAdapter(),
            new DemoCleanupAdapter()
        );

        // Create task
        $idGenerator = new UuidIdGenerator();
        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAuditLogger());
        $createCommand = new CreateTaskCommand('actor-456', 'idem-key-worker-2');
        $createResult = $createHandler->handle($createCommand);
        $taskIdString = $createResult->value();
        $taskId = TaskId::fromString($taskIdString);

        $tickHandler = new TickTaskHandler($taskRepo, $pipelineRepo, $outputRepo, $registry, $unitOfWork, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAuditLogger(), new \Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator(), new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryMetricsEmitter());

        // Process all stages via worker simulation
        $stages = ['parse', 'photos', 'publish', 'export', 'cleanup'];
        foreach ($stages as $expectedStage) {
            // Enqueue job
            $jobQueue->enqueueAdvance($taskId);

            // Worker claims and processes
            $claimedJob = $jobQueue->claimNext();
            $this->assertTrue($claimedJob !== null, "Should claim job for stage: $expectedStage");

            $command = new \Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand($claimedJob->taskId());
            $result = $tickHandler->handle($command);

            $this->assertTrue($result->isSuccess(), "Tick should succeed for stage: $expectedStage");
            $this->assertTrue(
                $result->value()['completed_stage'] === $expectedStage,
                "Should complete stage: $expectedStage"
            );

            $jobQueue->markSucceeded($claimedJob->jobId());
        }

        // Verify task is succeeded
        $task = $taskRepo->findById($taskId);
        $this->assertTrue($task->isSucceeded(), 'Task should be marked as succeeded');

        // Verify all outputs
        $outputs = $outputRepo->read($taskId);
        $this->assertTrue(count($outputs) === 5, 'Should have 5 outputs');
    }

    public function testWorkerHandlesFailure(): void
    {
        $pdo = $this->setupFreshDatabase();

        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();
        $jobQueue = new SQLiteJobQueue($pdo);

        // Use failing adapter for parse stage
        $failingParser = new class implements \Cabinet\Backend\Application\Integrations\ParserIntegration {
            public function run(TaskId $taskId): \Cabinet\Backend\Application\Shared\IntegrationResult {
                return \Cabinet\Backend\Application\Shared\IntegrationResult::failed(
                    \Cabinet\Contracts\ErrorKind::INTEGRATION_UNAVAILABLE,
                    true // retryable
                );
            }
        };

        $registry = new IntegrationRegistry(
            $failingParser,
            new DemoPhotosAdapter(),
            new DemoPublisherAdapter(),
            new DemoExportAdapter(),
            new DemoCleanupAdapter()
        );

        // Create task
        $idGenerator = new UuidIdGenerator();
        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAuditLogger());
        $createCommand = new CreateTaskCommand('actor-789', 'idem-key-worker-3');
        $createResult = $createHandler->handle($createCommand);
        $taskIdString = $createResult->value();
        $taskId = TaskId::fromString($taskIdString);

        $tickHandler = new TickTaskHandler($taskRepo, $pipelineRepo, $outputRepo, $registry, $unitOfWork, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAuditLogger(), new \Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator(), new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryMetricsEmitter());

        // Enqueue and process job
        $jobId = $jobQueue->enqueueAdvance($taskId);
        $claimedJob = $jobQueue->claimNext();

        $command = new \Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand($claimedJob->taskId());
        $result = $tickHandler->handle($command);

        $this->assertTrue($result->isSuccess(), 'Handler should complete even on integration failure');
        $this->assertTrue($result->value()['status'] === 'failed', 'Status should be failed');
        $this->assertTrue($result->value()['retryable'] === true, 'Should be retryable');

        // Worker marks job as failed with retry
        $jobQueue->markFailed(
            $claimedJob->jobId(),
            \Cabinet\Contracts\ErrorKind::INTEGRATION_UNAVAILABLE,
            true
        );

        // Verify job was requeued
        $stmt = $pdo->prepare('SELECT status FROM jobs WHERE job_id = :job_id');
        $stmt->execute(['job_id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertTrue($row['status'] === JobStatus::QUEUED->value, 'Job should be requeued for retry');
    }
}
