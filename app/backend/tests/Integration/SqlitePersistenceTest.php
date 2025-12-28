<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Integration;

use Cabinet\Backend\Tests\TestCase;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Infrastructure\Persistence\PDO\ConnectionFactory;

final class SqlitePersistenceTest extends TestCase
{
    private function resetDatabase(): void
    {
        // Reset the connection for a clean database per test
        ConnectionFactory::reset();
        
        // Remove the old database file
        $dbPath = getenv('DB_PATH') ?: '/tmp/cabinet.db';
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }

    public function testRequestAccessPersistsToDatabase(): void
    {
        $this->resetDatabase();
        $kernel = $this->createKernel();

        // Create an access request
        $request = new Request('POST', '/access/request', [], json_encode(['requestedBy' => 'test@example.com']));
        $response = $kernel->handle($request);

        $this->assertEquals(200, $response->statusCode(), 'Request access should succeed');
        
        $body = json_decode($response->body(), true);
        $accessRequestId = $body['accessRequestId'];

        // Verify it's in the database
        $pdo = ConnectionFactory::create();
        $stmt = $pdo->prepare('SELECT * FROM access_requests WHERE id = :id');
        $stmt->execute([':id' => $accessRequestId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($row !== false, 'Access request should be in database');
        $this->assertEquals('test@example.com', $row['requested_by'], 'Requested by should match');
        $this->assertEquals('pending', $row['status'], 'Status should be pending');
    }

    public function testCreateTaskPersistsTaskAndPipelineState(): void
    {
        $this->resetDatabase();
        
        // For this test we need to manually create and call the handler since we can't authenticate
        $config = \Cabinet\Backend\Bootstrap\Config::fromEnvironment();
        $clock = new \Cabinet\Backend\Bootstrap\Clock();
        $container = new \Cabinet\Backend\Bootstrap\Container($config, $clock);

        $command = new \Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand(
            'test-actor-1',
            'test-key-1'
        );

        $result = $container->commandBus()->dispatch($command);

        $this->assertTrue($result->isSuccess(), 'Create task should succeed');
        
        $taskId = $result->value();

        // Verify task is in database
        $pdo = ConnectionFactory::create();
        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = :id');
        $stmt->execute([':id' => $taskId]);
        $taskRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($taskRow !== false, 'Task should be in database');
        $this->assertEquals('open', $taskRow['status'], 'Task status should be open');

        // Verify pipeline state is in database
        $stmt = $pdo->prepare('SELECT * FROM pipeline_states WHERE task_id = :task_id');
        $stmt->execute([':task_id' => $taskId]);
        $pipelineRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($pipelineRow !== false, 'Pipeline state should be in database');
        $this->assertEquals('parse', $pipelineRow['stage'], 'Stage should be parse');
        $this->assertEquals('queued', $pipelineRow['status'], 'Status should be queued');
    }

    public function testIdempotencyKeyEnforcesUniqueness(): void
    {
        $this->resetDatabase();
        
        $config = \Cabinet\Backend\Bootstrap\Config::fromEnvironment();
        $clock = new \Cabinet\Backend\Bootstrap\Clock();
        $container = new \Cabinet\Backend\Bootstrap\Container($config, $clock);

        // Create first task
        $command1 = new \Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand(
            'test-actor-2',
            'test-key-unique'
        );

        $result1 = $container->commandBus()->dispatch($command1);
        $this->assertTrue($result1->isSuccess(), 'First create task should succeed');
        $taskId1 = $result1->value();

        // Try to create again with same actor and idempotency key
        $command2 = new \Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand(
            'test-actor-2',
            'test-key-unique'
        );

        $result2 = $container->commandBus()->dispatch($command2);
        $this->assertTrue($result2->isSuccess(), 'Second create task should succeed (idempotent)');
        $taskId2 = $result2->value();

        // Should return the same task ID
        $this->assertEquals($taskId1, $taskId2, 'Should return same task ID for idempotent request');

        // Verify only one task exists in database
        $pdo = ConnectionFactory::create();
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM tasks');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, (int)$row['count'], 'Should only have one task in database');

        // Verify idempotency key is stored
        $stmt = $pdo->prepare('SELECT * FROM idempotency_keys WHERE actor_id = :actor_id AND idem_key = :idem_key');
        $stmt->execute([':actor_id' => 'test-actor-2', ':idem_key' => 'test-key-unique']);
        $idempRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($idempRow !== false, 'Idempotency key should be in database');
        $this->assertEquals($taskId1, $idempRow['task_id'], 'Idempotency key should map to task ID');
    }

    public function testIdempotencyAcrossProcessRestart(): void
    {
        $this->resetDatabase();
        
        // First "process" - create task
        $config1 = \Cabinet\Backend\Bootstrap\Config::fromEnvironment();
        $clock1 = new \Cabinet\Backend\Bootstrap\Clock();
        $container1 = new \Cabinet\Backend\Bootstrap\Container($config1, $clock1);

        $command1 = new \Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand(
            'test-actor-3',
            'test-key-restart'
        );

        $result1 = $container1->commandBus()->dispatch($command1);
        $taskId1 = $result1->value();

        // Simulate process restart by creating new container
        ConnectionFactory::reset();
        
        // Second "process" - try to create same task
        $config2 = \Cabinet\Backend\Bootstrap\Config::fromEnvironment();
        $clock2 = new \Cabinet\Backend\Bootstrap\Clock();
        $container2 = new \Cabinet\Backend\Bootstrap\Container($config2, $clock2);

        $command2 = new \Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand(
            'test-actor-3',
            'test-key-restart'
        );

        $result2 = $container2->commandBus()->dispatch($command2);
        $taskId2 = $result2->value();

        // Should return the same task ID even after "restart"
        $this->assertEquals($taskId1, $taskId2, 'Should return same task ID after process restart');
    }

    public function testApproveAccessCreatesPersistedUser(): void
    {
        $this->resetDatabase();
        
        $config = \Cabinet\Backend\Bootstrap\Config::fromEnvironment();
        $clock = new \Cabinet\Backend\Bootstrap\Clock();
        $container = new \Cabinet\Backend\Bootstrap\Container($config, $clock);

        // First create an access request
        $requestCommand = new \Cabinet\Backend\Application\Commands\Access\RequestAccessCommand('newuser@example.com');
        $requestResult = $container->commandBus()->dispatch($requestCommand);
        $this->assertTrue($requestResult->isSuccess(), 'Request access should succeed');
        $accessRequestId = $requestResult->value();

        // Now approve it
        $approveCommand = new \Cabinet\Backend\Application\Commands\Access\ApproveAccessCommand(
            $accessRequestId,
            'approver-123'
        );
        $approveResult = $container->commandBus()->dispatch($approveCommand);
        $this->assertTrue($approveResult->isSuccess(), 'Approve access should succeed');
        $userId = $approveResult->value();

        // Verify user is in database
        $pdo = ConnectionFactory::create();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $userRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($userRow !== false, 'User should be in database');
        $this->assertEquals('user', $userRow['role'], 'User role should be user');
        $this->assertEquals(1, (int)$userRow['is_active'], 'User should be active');

        // Verify access request status is updated
        $stmt = $pdo->prepare('SELECT * FROM access_requests WHERE id = :id');
        $stmt->execute([':id' => $accessRequestId]);
        $accessRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue($accessRow !== false, 'Access request should be in database');
        $this->assertEquals('approved', $accessRow['status'], 'Access request should be approved');
    }
}
