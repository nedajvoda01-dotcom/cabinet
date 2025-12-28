<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Integration;

use Cabinet\Backend\Application\Ports\TaskOutputRepository;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Infrastructure\Persistence\PDO\ConnectionFactory;
use Cabinet\Backend\Infrastructure\Persistence\PDO\MigrationsRunner;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\TaskOutputsRepository;
use Cabinet\Backend\Tests\TestCase;
use Cabinet\Contracts\PipelineStage;

final class TaskOutputsRepositoryTest extends TestCase
{
    private function createRepository(): TaskOutputRepository
    {
        $pdo = ConnectionFactory::create();
        $migrations = new MigrationsRunner($pdo);
        $migrations->run();
        
        return new TaskOutputsRepository($pdo);
    }

    public function testWriteAndReadSingleOutput(): void
    {
        $repository = $this->createRepository();
        $taskId = TaskId::fromString('task-123');
        $payload = ['source' => 'demo', 'items' => 3];

        $repository->write($taskId, PipelineStage::PARSE, $payload);

        $outputs = $repository->read($taskId);

        $this->assertTrue(isset($outputs['parse']), 'Parse stage should exist in outputs');
        $this->assertTrue($outputs['parse']['payload'] === $payload, 'Payload should match');
    }

    public function testWriteMultipleStages(): void
    {
        $repository = $this->createRepository();
        $taskId = TaskId::fromString('task-456');

        $repository->write($taskId, PipelineStage::PARSE, ['step' => 'parse']);
        $repository->write($taskId, PipelineStage::PHOTOS, ['step' => 'photos']);
        $repository->write($taskId, PipelineStage::PUBLISH, ['step' => 'publish']);

        $outputs = $repository->read($taskId);

        $this->assertTrue(count($outputs) === 3, 'Should have 3 outputs');
        $this->assertTrue(isset($outputs['parse']), 'Parse should exist');
        $this->assertTrue(isset($outputs['photos']), 'Photos should exist');
        $this->assertTrue(isset($outputs['publish']), 'Publish should exist');
    }

    public function testOutputsOrderedByStage(): void
    {
        $repository = $this->createRepository();
        $taskId = TaskId::fromString('task-789');

        // Write in random order
        $repository->write($taskId, PipelineStage::CLEANUP, ['step' => 'cleanup']);
        $repository->write($taskId, PipelineStage::PARSE, ['step' => 'parse']);
        $repository->write($taskId, PipelineStage::EXPORT, ['step' => 'export']);
        $repository->write($taskId, PipelineStage::PHOTOS, ['step' => 'photos']);
        $repository->write($taskId, PipelineStage::PUBLISH, ['step' => 'publish']);

        $outputs = $repository->read($taskId);
        $keys = array_keys($outputs);

        // Should be ordered: parse, photos, publish, export, cleanup
        $this->assertTrue($keys[0] === 'parse', 'First should be parse');
        $this->assertTrue($keys[1] === 'photos', 'Second should be photos');
        $this->assertTrue($keys[2] === 'publish', 'Third should be publish');
        $this->assertTrue($keys[3] === 'export', 'Fourth should be export');
        $this->assertTrue($keys[4] === 'cleanup', 'Fifth should be cleanup');
    }

    public function testUpsertUpdatesExisting(): void
    {
        $repository = $this->createRepository();
        $taskId = TaskId::fromString('task-upsert');
        
        // Write initial payload
        $repository->write($taskId, PipelineStage::PARSE, ['version' => 1]);
        
        // Write updated payload
        $repository->write($taskId, PipelineStage::PARSE, ['version' => 2, 'updated' => true]);

        $outputs = $repository->read($taskId);

        $this->assertTrue(count($outputs) === 1, 'Should have only 1 output (upserted)');
        $this->assertTrue($outputs['parse']['payload']['version'] === 2, 'Version should be updated');
        $this->assertTrue($outputs['parse']['payload']['updated'] === true, 'Should have updated flag');
    }

    public function testReadNonExistentTaskReturnsEmpty(): void
    {
        $repository = $this->createRepository();
        $taskId = TaskId::fromString('non-existent-task');
        
        $outputs = $repository->read($taskId);

        $this->assertTrue($outputs === [], 'Should return empty array for non-existent task');
    }
}
