<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidStateTransition;
use Cabinet\Backend\Domain\Users\AccessRequest;
use Cabinet\Backend\Domain\Users\AccessRequestId;
use Cabinet\Backend\Domain\Users\AccessRequestStatus;
use Cabinet\Backend\Domain\Users\UserId;
use Cabinet\Backend\Tests\TestCase;

final class AccessRequestTest extends TestCase
{
    public function testCreateAccessRequest(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        
        $this->assertTrue($request->id()->equals($id));
        $this->assertTrue($request->userId()->equals($userId));
        $this->assertTrue($request->isPending());
        $this->assertTrue(!$request->isApproved());
        $this->assertTrue(!$request->isRejected());
    }

    public function testApproveAccessRequest(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        $request->approve();
        
        $this->assertTrue($request->isApproved());
        $this->assertTrue(!$request->isPending());
        $this->assertTrue(!$request->isRejected());
    }

    public function testRejectAccessRequest(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        $request->reject();
        
        $this->assertTrue($request->isRejected());
        $this->assertTrue(!$request->isPending());
        $this->assertTrue(!$request->isApproved());
    }

    public function testCannotApproveAlreadyApproved(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        $request->approve();
        
        try {
            $request->approve();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotRejectAlreadyRejected(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        $request->reject();
        
        try {
            $request->reject();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotApproveAfterRejection(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        $request->reject();
        
        try {
            $request->approve();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testCannotRejectAfterApproval(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        $request->approve();
        
        try {
            $request->reject();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testStatusAccessor(): void
    {
        $id = AccessRequestId::fromString('req-123');
        $userId = UserId::fromString('user-123');
        
        $request = AccessRequest::create($id, $userId);
        
        $this->assertEquals(AccessRequestStatus::PENDING, $request->status());
        
        $request->approve();
        $this->assertEquals(AccessRequestStatus::APPROVED, $request->status());
    }
}
