<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Application;

use Cabinet\Backend\Application\Commands\Access\ApproveAccessCommand;
use Cabinet\Backend\Application\Commands\Access\RequestAccessCommand;
use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Handlers\ApproveAccessHandler;
use Cabinet\Backend\Application\Handlers\CreateTaskHandler;
use Cabinet\Backend\Application\Handlers\RequestAccessHandler;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAccessRequestRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryPipelineStateRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryTaskRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryUserRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator;
use Cabinet\Backend\Tests\TestCase;

final class HandlersTest extends TestCase
{
    public function testRequestAccessHandlerCreatesAccessRequest(): void
    {
        $repo = new InMemoryAccessRequestRepository();
        $idGen = new UuidIdGenerator();
        $handler = new RequestAccessHandler($repo, $idGen);

        $command = new RequestAccessCommand('user@example.com');
        $result = $handler->handle($command);

        $this->assertTrue($result->isSuccess(), 'Result should be success');
        $this->assertNotEmpty($result->value(), 'Access request ID should not be empty');
    }

    public function testApproveAccessHandlerCreatesUserFromRequest(): void
    {
        $accessRequestRepo = new InMemoryAccessRequestRepository();
        $userRepo = new InMemoryUserRepository();
        $idGen = new UuidIdGenerator();

        // First create an access request
        $requestHandler = new RequestAccessHandler($accessRequestRepo, $idGen);
        $requestCommand = new RequestAccessCommand('user@example.com');
        $requestResult = $requestHandler->handle($requestCommand);
        $accessRequestId = $requestResult->value();

        // Now approve it
        $approveHandler = new ApproveAccessHandler($accessRequestRepo, $userRepo, $idGen);
        $approveCommand = new ApproveAccessCommand($accessRequestId, 'admin-123');
        $approveResult = $approveHandler->handle($approveCommand);

        $this->assertTrue($approveResult->isSuccess(), 'Approve should succeed');
        $this->assertNotEmpty($approveResult->value(), 'User ID should not be empty');
    }

    public function testApproveAccessHandlerFailsForNonExistentRequest(): void
    {
        $accessRequestRepo = new InMemoryAccessRequestRepository();
        $userRepo = new InMemoryUserRepository();
        $idGen = new UuidIdGenerator();

        $handler = new ApproveAccessHandler($accessRequestRepo, $userRepo, $idGen);
        $command = new ApproveAccessCommand('non-existent-id', 'admin-123');
        $result = $handler->handle($command);

        $this->assertTrue($result->isFailure(), 'Result should be failure');
        $this->assertTrue($result->error()->code()->value === 'not_found', 'Error code should be not_found');
    }

    public function testCreateTaskHandlerCreatesTaskAndPipelineState(): void
    {
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $idGen = new UuidIdGenerator();

        $handler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGen);
        $command = new CreateTaskCommand('actor-123', 'idempotency-key-1');
        $result = $handler->handle($command);

        $this->assertTrue($result->isSuccess(), 'Result should be success');
        $this->assertNotEmpty($result->value(), 'Task ID should not be empty');
    }

    public function testCreateTaskHandlerEnforcesIdempotency(): void
    {
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $idGen = new UuidIdGenerator();

        $handler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGen);
        
        // First creation
        $command1 = new CreateTaskCommand('actor-123', 'idempotency-key-1');
        $result1 = $handler->handle($command1);
        $taskId1 = $result1->value();

        // Second creation with same actor and idempotency key
        $command2 = new CreateTaskCommand('actor-123', 'idempotency-key-1');
        $result2 = $handler->handle($command2);
        $taskId2 = $result2->value();

        $this->assertTrue($taskId1 === $taskId2, 'Same idempotency key should return same task ID');
    }

    public function testCreateTaskHandlerAllowsDifferentActorsSameKey(): void
    {
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();
        $idGen = new UuidIdGenerator();

        $handler = new CreateTaskHandler($taskRepo, $pipelineRepo, $idGen);
        
        // First creation by actor-123
        $command1 = new CreateTaskCommand('actor-123', 'idempotency-key-1');
        $result1 = $handler->handle($command1);
        $taskId1 = $result1->value();

        // Second creation by actor-456 with same idempotency key
        $command2 = new CreateTaskCommand('actor-456', 'idempotency-key-1');
        $result2 = $handler->handle($command2);
        $taskId2 = $result2->value();

        $this->assertTrue($taskId1 !== $taskId2, 'Different actors should get different task IDs');
    }
}
