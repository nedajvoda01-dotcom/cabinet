<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Integration;

use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Handlers\CreateTaskHandler;
use Cabinet\Backend\Application\Handlers\TickTaskHandler;
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
use Cabinet\Backend\Infrastructure\Observability\SQLiteAuditLogger;
use Cabinet\Backend\Tests\TestCase;

final class AuditTrailIntegrationTest extends TestCase
{
    private function setupFreshDatabase(): \PDO
    {
        $dbPath = '/tmp/test-audit-' . uniqid() . '.db';
        putenv('DB_PATH=' . $dbPath);
        
        ConnectionFactory::reset();
        $pdo = ConnectionFactory::create();
        $migrations = new MigrationsRunner($pdo);
        $migrations->run();
        
        return $pdo;
    }

    public function testAuditEventsAreRecordedForTaskCreationAndPipelineExecution(): void
    {
        $pdo = $this->setupFreshDatabase();

        // Setup repositories
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();
        $jobQueue = new SQLiteJobQueue($pdo, new SQLiteAuditLogger($pdo));
        $auditLogger = new SQLiteAuditLogger($pdo);
        $idGenerator = new UuidIdGenerator();

        // Setup integrations
        $registry = new IntegrationRegistry(
            new DemoParserAdapter(),
            new DemoPhotosAdapter(),
            new DemoPublisherAdapter(),
            new DemoExportAdapter(),
            new DemoCleanupAdapter()
        );

        // Create a task
        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, $auditLogger);
        $createCommand = new CreateTaskCommand('actor-audit-test', 'idem-key-audit-1');
        $createResult = $createHandler->handle($createCommand);
        
        $this->assertTrue($createResult->isSuccess(), 'Task creation should succeed');
        $taskIdString = $createResult->value();
        $taskId = TaskId::fromString($taskIdString);

        // Enqueue the job
        $jobId = $jobQueue->enqueueAdvance($taskId);

        // Run one worker iteration
        $metricsEmitter = new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryMetricsEmitter();
        $tickHandler = new TickTaskHandler($taskRepo, $pipelineRepo, $outputRepo, $registry, $unitOfWork, $auditLogger, $idGenerator, $metricsEmitter);
        $claimedJob = $jobQueue->claimNext();
        $this->assertTrue($claimedJob !== null, 'Worker should claim a job');

        $command = new \Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand($claimedJob->taskId());
        $result = $tickHandler->handle($command);
        $this->assertTrue($result->isSuccess(), 'Tick should succeed');

        $jobQueue->markSucceeded($claimedJob->jobId());

        // Verify audit events were recorded
        $stmt = $pdo->prepare('SELECT action, target_type, target_id FROM audit_events ORDER BY created_at ASC');
        $stmt->execute();
        $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertTrue(count($events) >= 4, 'Should have at least 4 audit events');

        // Check for expected audit events
        $actions = array_column($events, 'action');
        
        $this->assertTrue(in_array('task.created', $actions), 'Should have task.created event');
        $this->assertTrue(in_array('job.enqueued', $actions), 'Should have job.enqueued event');
        $this->assertTrue(in_array('job.claimed', $actions), 'Should have job.claimed event');
        $this->assertTrue(in_array('pipeline.stage.succeeded', $actions), 'Should have pipeline.stage.succeeded event');
        $this->assertTrue(in_array('job.succeeded', $actions), 'Should have job.succeeded event');
    }

    public function testIdempotencyHitIsAudited(): void
    {
        $pdo = $this->setupFreshDatabase();

        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $auditLogger = new SQLiteAuditLogger($pdo);
        $idGenerator = new UuidIdGenerator();

        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, $auditLogger);
        
        // Create task first time
        $createCommand = new CreateTaskCommand('actor-idem-test', 'idem-key-duplicate');
        $createResult1 = $createHandler->handle($createCommand);
        $this->assertTrue($createResult1->isSuccess(), 'First task creation should succeed');

        // Create task second time with same idempotency key
        $createResult2 = $createHandler->handle($createCommand);
        $this->assertTrue($createResult2->isSuccess(), 'Second task creation should succeed (idempotent)');
        $this->assertTrue($createResult1->value() === $createResult2->value(), 'Should return same task ID');

        // Verify audit events
        $stmt = $pdo->prepare('SELECT action FROM audit_events WHERE action LIKE "task.%"');
        $stmt->execute();
        $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $actions = array_column($events, 'action');
        $this->assertTrue(in_array('task.created', $actions), 'Should have task.created event');
        $this->assertTrue(in_array('task.create.idempotency_hit', $actions), 'Should have idempotency_hit event');
    }
}
