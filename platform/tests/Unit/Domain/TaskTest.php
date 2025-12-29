<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidStateTransition;
use Cabinet\Backend\Domain\Tasks\Task;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Domain\Tasks\TaskStatus;
use Cabinet\Backend\Tests\TestCase;

final class TaskTest extends TestCase
{
    public function testCreateTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $this->assertTrue($task->id()->equals($id));
        $this->assertTrue($task->isOpen());
        $this->assertEquals(TaskStatus::OPEN, $task->status());
    }

    public function testStartTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        
        $this->assertTrue($task->isRunning());
        $this->assertTrue(!$task->isOpen());
    }

    public function testMarkSucceeded(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markSucceeded();
        
        $this->assertTrue($task->isSucceeded());
        $this->assertTrue(!$task->isRunning());
    }

    public function testMarkFailed(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markFailed();
        
        $this->assertTrue($task->isFailed());
        $this->assertTrue(!$task->isRunning());
    }

    public function testCancel(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->cancel();
        
        $this->assertTrue($task->isCancelled());
        $this->assertTrue(!$task->isOpen());
    }

    public function testCanCancelRunningTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->cancel();
        
        $this->assertTrue($task->isCancelled());
    }

    public function testCannotStartSucceededTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markSucceeded();
        
        try {
            $task->start();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotStartFailedTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markFailed();
        
        try {
            $task->start();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotStartCancelledTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->cancel();
        
        try {
            $task->start();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotMarkSucceededAfterFailed(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markFailed();
        
        try {
            $task->markSucceeded();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotMarkFailedAfterSucceeded(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markSucceeded();
        
        try {
            $task->markFailed();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotCancelSucceededTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markSucceeded();
        
        try {
            $task->cancel();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotCancelFailedTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->start();
        $task->markFailed();
        
        try {
            $task->cancel();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotCancelAlreadyCancelledTask(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        $task->cancel();
        
        try {
            $task->cancel();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testSucceedDirectlyFromOpen(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        // Can succeed without going to running first
        $task->markSucceeded();
        
        $this->assertTrue($task->isSucceeded());
    }

    public function testFailDirectlyFromOpen(): void
    {
        $id = TaskId::fromString('task-123');
        $task = Task::create($id);
        
        // Can fail without going to running first
        $task->markFailed();
        
        $this->assertTrue($task->isFailed());
    }
}
