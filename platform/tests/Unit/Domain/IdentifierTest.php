<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidIdentifier;
use Cabinet\Backend\Domain\Users\UserId;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Users\AccessRequestId;
use Cabinet\Backend\Tests\TestCase;

final class IdentifierTest extends TestCase
{
    public function testUserIdFromString(): void
    {
        $id = UserId::fromString('user-123');
        $this->assertEquals('user-123', $id->toString());
    }

    public function testUserIdEquals(): void
    {
        $id1 = UserId::fromString('user-123');
        $id2 = UserId::fromString('user-123');
        $id3 = UserId::fromString('user-456');
        
        $this->assertTrue($id1->equals($id2), 'Same IDs should be equal');
        $this->assertTrue(!$id1->equals($id3), 'Different IDs should not be equal');
    }

    public function testUserIdRejectsEmpty(): void
    {
        try {
            UserId::fromString('');
            throw new \Exception('Should have thrown InvalidIdentifier');
        } catch (InvalidIdentifier $e) {
            $this->assertTrue(true);
        }
    }

    public function testUserIdRejectsTooLong(): void
    {
        try {
            UserId::fromString(str_repeat('a', 256));
            throw new \Exception('Should have thrown InvalidIdentifier');
        } catch (InvalidIdentifier $e) {
            $this->assertTrue(true);
        }
    }

    public function testTaskIdFromString(): void
    {
        $id = TaskId::fromString('task-123');
        $this->assertEquals('task-123', $id->toString());
    }

    public function testTaskIdEquals(): void
    {
        $id1 = TaskId::fromString('task-123');
        $id2 = TaskId::fromString('task-123');
        $id3 = TaskId::fromString('task-456');
        
        $this->assertTrue($id1->equals($id2), 'Same IDs should be equal');
        $this->assertTrue(!$id1->equals($id3), 'Different IDs should not be equal');
    }

    public function testJobIdFromString(): void
    {
        $id = JobId::fromString('job-123');
        $this->assertEquals('job-123', $id->toString());
    }

    public function testJobIdEquals(): void
    {
        $id1 = JobId::fromString('job-123');
        $id2 = JobId::fromString('job-123');
        $id3 = JobId::fromString('job-456');
        
        $this->assertTrue($id1->equals($id2), 'Same IDs should be equal');
        $this->assertTrue(!$id1->equals($id3), 'Different IDs should not be equal');
    }

    public function testAccessRequestIdFromString(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $this->assertEquals('req-123', $id->toString());
    }

    public function testAccessRequestIdEquals(): void
    {
        $id1 = AccessRequestId::fromString('req-123');
        $id2 = AccessRequestId::fromString('req-123');
        $id3 = AccessRequestId::fromString('req-456');
        
        $this->assertTrue($id1->equals($id2), 'Same IDs should be equal');
        $this->assertTrue(!$id1->equals($id3), 'Different IDs should not be equal');
    }
}
