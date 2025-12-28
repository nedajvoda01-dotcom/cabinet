<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Integration;

use Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand;
use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Handlers\TickTaskHandler;
use Cabinet\Backend\Application\Handlers\CreateTaskHandler;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineState;
use Cabinet\Backend\Domain\Tasks\Task;
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
use Cabinet\Backend\Infrastructure\Persistence\InMemory\NoOpUnitOfWork;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator;
use Cabinet\Backend\Infrastructure\Persistence\PDO\ConnectionFactory;
use Cabinet\Backend\Infrastructure\Persistence\PDO\MigrationsRunner;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\TaskOutputsRepository;
use Cabinet\Backend\Tests\TestCase;
use Cabinet\Contracts\PipelineStage;

final class PipelineTickIntegrationTest extends TestCase
{
    public function testTickAdvancesThroughAllStages(): void
    {
        // Setup repositories
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $pdo = ConnectionFactory::create();
        $migrations = new MigrationsRunner($pdo);
        $migrations->run();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();

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
        $auditLogger = new InMemoryAuditLogger();
        $createHandler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGenerator, $auditLogger);
        $createCommand = new CreateTaskCommand('actor-123', 'idem-key-1');
        $createResult = $createHandler->handle($createCommand);
        
        $this->assertTrue($createResult->isSuccess(), 'Task creation should succeed');
        $taskIdString = $createResult->value();
        $taskId = TaskId::fromString($taskIdString);
        $jobId = JobId::fromString($taskIdString);

        // Create tick handler
        $tickHandler = new TickTaskHandler($taskRepo, $pipelineRepo, $outputRepo, $registry, $unitOfWork, $auditLogger, $idGenerator, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryMetricsEmitter());

        // Tick 1: Parse stage
        $tickCommand = new TickTaskCommand($taskIdString);
        $result = $tickHandler->handle($tickCommand);
        
        $this->assertTrue($result->isSuccess(), 'First tick should succeed');
        $this->assertTrue($result->value()['status'] === 'advanced', 'Should advance');
        $this->assertTrue($result->value()['completed_stage'] === 'parse', 'Should complete parse');
        $this->assertTrue($result->value()['next_stage'] === 'photos', 'Should advance to photos');

        // Verify output was written
        $outputs = $outputRepo->read($taskId);
        $this->assertTrue(isset($outputs['parse']), 'Parse output should exist');
        $this->assertTrue($outputs['parse']['payload']['source'] === 'demo', 'Parse output should match');

        // Tick 2: Photos stage
        $result = $tickHandler->handle($tickCommand);
        $this->assertTrue($result->value()['completed_stage'] === 'photos', 'Should complete photos');
        $this->assertTrue($result->value()['next_stage'] === 'publish', 'Should advance to publish');

        // Tick 3: Publish stage
        $result = $tickHandler->handle($tickCommand);
        $this->assertTrue($result->value()['completed_stage'] === 'publish', 'Should complete publish');
        $this->assertTrue($result->value()['next_stage'] === 'export', 'Should advance to export');

        // Tick 4: Export stage
        $result = $tickHandler->handle($tickCommand);
        $this->assertTrue($result->value()['completed_stage'] === 'export', 'Should complete export');
        $this->assertTrue($result->value()['next_stage'] === 'cleanup', 'Should advance to cleanup');
        
        // Verify export output has task ID in URL
        $outputs = $outputRepo->read($taskId);
        $this->assertTrue(strpos($outputs['export']['payload']['url'], $taskIdString) !== false, 'Export URL should contain task ID');

        // Tick 5: Cleanup stage (final)
        $result = $tickHandler->handle($tickCommand);
        $this->assertTrue($result->value()['completed_stage'] === 'cleanup', 'Should complete cleanup');
        $this->assertTrue($result->value()['next_stage'] === null, 'Should have no next stage');
        $this->assertTrue($result->value()['task_status'] === 'succeeded', 'Task should be succeeded');

        // Verify task is marked as succeeded
        $task = $taskRepo->findById($taskId);
        $this->assertTrue($task->isSucceeded(), 'Task should be marked as succeeded');

        // Verify pipeline is done
        $pipelineState = $pipelineRepo->findByJobId($jobId);
        $this->assertTrue($pipelineState->isDone(), 'Pipeline should be done');

        // Verify all outputs were written
        $outputs = $outputRepo->read($taskId);
        $this->assertTrue(count($outputs) === 5, 'Should have 5 outputs');
        $this->assertTrue(isset($outputs['parse']), 'Parse output should exist');
        $this->assertTrue(isset($outputs['photos']), 'Photos output should exist');
        $this->assertTrue(isset($outputs['publish']), 'Publish output should exist');
        $this->assertTrue(isset($outputs['export']), 'Export output should exist');
        $this->assertTrue(isset($outputs['cleanup']), 'Cleanup output should exist');

        // Tick 6: After done, should return done status
        $result = $tickHandler->handle($tickCommand);
        $this->assertTrue($result->value()['status'] === 'done', 'Should return done status');
    }

    public function testTickNonExistentTask(): void
    {
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $pdo = ConnectionFactory::create();
        $migrations = new MigrationsRunner($pdo);
        $migrations->run();
        $outputRepo = new TaskOutputsRepository($pdo);
        $unitOfWork = new NoOpUnitOfWork();

        $registry = new IntegrationRegistry(
            new DemoParserAdapter(),
            new DemoPhotosAdapter(),
            new DemoPublisherAdapter(),
            new DemoExportAdapter(),
            new DemoCleanupAdapter()
        );

        $idGenerator = new UuidIdGenerator();
        $auditLogger = new InMemoryAuditLogger();
        $tickHandler = new TickTaskHandler($taskRepo, $pipelineRepo, $outputRepo, $registry, $unitOfWork, $auditLogger, $idGenerator, new \Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryMetricsEmitter());
        $tickCommand = new TickTaskCommand('non-existent-task');
        $result = $tickHandler->handle($tickCommand);

        $this->assertTrue($result->isFailure(), 'Should fail for non-existent task');
        $this->assertTrue($result->error()->code()->value === 'not_found', 'Error should be not_found');
    }
}
