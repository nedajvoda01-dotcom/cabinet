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
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAuditLogger;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryMetricsEmitter;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\NoOpUnitOfWork;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator;
use Cabinet\Backend\Infrastructure\Persistence\PDO\ConnectionFactory;
use Cabinet\Backend\Infrastructure\Persistence\PDO\MigrationsRunner;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\TaskOutputsRepository;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\SQLiteJobQueue;
use Cabinet\Backend\Tests\TestCase;

final class MetricsIntegrationTest extends TestCase
{
    private function setupFreshDatabase(): \PDO
    {
        $dbPath = '/tmp/test-metrics-' . uniqid() . '.db';
        putenv('DB_PATH=' . $dbPath);
        
        ConnectionFactory::reset();
        $pdo = ConnectionFactory::create();
        $migrations = new MigrationsRunner($pdo);
        $migrations->run();
        
        return $pdo;
    }

    public function testPipelineSuccessEmitsMetrics(): void
    {
        $pdo = $this->setupFreshDatabase();

        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();
        $auditLogger = new InMemoryAuditLogger();
        $metricsEmitter = new InMemoryMetricsEmitter();
        $idGenerator = new UuidIdGenerator();

        $registry = new IntegrationRegistry(
            new DemoParserAdapter(),
            new DemoPhotosAdapter(),
            new DemoPublisherAdapter(),
            new DemoExportAdapter(),
            new DemoCleanupAdapter()
        );

        // Create task
        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, $auditLogger);
        $createCommand = new CreateTaskCommand('actor-metrics-test', 'idem-key-metrics-1');
        $createResult = $createHandler->handle($createCommand);
        $this->assertTrue($createResult->isSuccess(), 'Task creation should succeed');

        $taskIdString = $createResult->value();
        $taskId = TaskId::fromString($taskIdString);

        // Execute pipeline stage
        $tickHandler = new TickTaskHandler(
            $taskRepo,
            $pipelineRepo,
            $outputRepo,
            $registry,
            $unitOfWork,
            $auditLogger,
            $idGenerator,
            $metricsEmitter
        );

        $command = new \Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand($taskIdString);
        $result = $tickHandler->handle($command);
        $this->assertTrue($result->isSuccess(), 'Tick should succeed');

        // Verify metrics were emitted
        $metrics = $metricsEmitter->getMetrics();
        $this->assertTrue(count($metrics) > 0, 'Should have emitted at least one metric');

        $metricNames = array_column($metrics, 'name');
        $this->assertTrue(in_array('pipeline.stage.succeeded', $metricNames), 'Should have pipeline.stage.succeeded metric');
    }

    public function testJobRetryEmitsMetrics(): void
    {
        $pdo = $this->setupFreshDatabase();

        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();
        $auditLogger = new InMemoryAuditLogger();
        $metricsEmitter = new InMemoryMetricsEmitter();
        $idGenerator = new UuidIdGenerator();
        $jobQueue = new SQLiteJobQueue($pdo, $auditLogger, $metricsEmitter);

        // Use failing adapter
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
        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, $auditLogger);
        $createCommand = new CreateTaskCommand('actor-retry-metrics', 'idem-key-retry-metrics');
        $createResult = $createHandler->handle($createCommand);
        $taskIdString = $createResult->value();
        $taskId = TaskId::fromString($taskIdString);

        // Enqueue and process (will fail)
        $jobId = $jobQueue->enqueueAdvance($taskId);
        $claimedJob = $jobQueue->claimNext();

        $tickHandler = new TickTaskHandler(
            $taskRepo,
            $pipelineRepo,
            $outputRepo,
            $registry,
            $unitOfWork,
            $auditLogger,
            $idGenerator,
            $metricsEmitter
        );

        $command = new \Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand($claimedJob->taskId());
        $result = $tickHandler->handle($command);

        // Mark job as failed (will schedule retry)
        $jobQueue->markFailed(
            $claimedJob->jobId(),
            \Cabinet\Contracts\ErrorKind::INTEGRATION_UNAVAILABLE,
            true
        );

        // Verify metrics
        $this->assertTrue($metricsEmitter->hasMetric('job.retry_scheduled'), 'Should have job.retry_scheduled metric');
    }
}
