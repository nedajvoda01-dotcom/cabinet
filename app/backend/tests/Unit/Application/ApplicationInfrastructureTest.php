<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Application;

use Cabinet\Backend\Application\Bus\CommandBus;
use Cabinet\Backend\Application\Shared\ApplicationError;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAccessRequestRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryPipelineStateRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryTaskRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryUserRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\NoOpUnitOfWork;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator;
use Cabinet\Backend\Tests\TestCase;

final class ApplicationInfrastructureTest extends TestCase
{
    public function testInMemoryRepositoriesCanBeInstantiated(): void
    {
        $userRepo = new InMemoryUserRepository();
        $accessRequestRepo = new InMemoryAccessRequestRepository();
        $taskRepo = new InMemoryTaskRepository();
        $pipelineStateRepo = new InMemoryPipelineStateRepository();
        $unitOfWork = new NoOpUnitOfWork();
        $idGenerator = new UuidIdGenerator();

        $this->assertTrue(true, 'Repositories instantiated successfully');
    }

    public function testIdGeneratorProducesValidUuids(): void
    {
        $generator = new UuidIdGenerator();
        $id1 = $generator->generate();
        $id2 = $generator->generate();

        $this->assertTrue($id1 !== $id2, 'Generated IDs should be unique');
        $this->assertTrue(strlen($id1) === 36, 'UUID should be 36 characters');
        $this->assertTrue(strlen($id2) === 36, 'UUID should be 36 characters');
    }

    public function testCommandBusCanBeCreated(): void
    {
        $bus = new CommandBus();
        $this->assertTrue(true, 'CommandBus instantiated successfully');
    }

    public function testResultCanRepresentSuccess(): void
    {
        $result = Result::success('test-value');

        $this->assertTrue($result->isSuccess(), 'Result should be success');
        $this->assertTrue($result->value() === 'test-value', 'Result should contain value');
    }

    public function testResultCanRepresentFailure(): void
    {
        $error = ApplicationError::notFound('Test not found');
        $result = Result::failure($error);

        $this->assertTrue($result->isFailure(), 'Result should be failure');
        $this->assertTrue($result->error()->message() === 'Test not found', 'Result should contain error');
    }
}
