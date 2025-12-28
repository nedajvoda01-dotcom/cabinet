<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Shared\ValueObject\HierarchyRole;
use Cabinet\Backend\Domain\Shared\ValueObject\Scope;
use Cabinet\Backend\Domain\Shared\ValueObject\ScopeSet;
use Cabinet\Backend\Domain\Users\User;
use Cabinet\Backend\Domain\Users\UserId;
use Cabinet\Backend\Tests\TestCase;

final class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $id = UserId::fromString('user-123');
        $role = HierarchyRole::user();
        $scopes = ScopeSet::fromScopes([]);
        $timestamp = 1640000000;
        
        $user = User::create($id, $role, $scopes, $timestamp);
        
        $this->assertTrue($user->id()->equals($id));
        $this->assertTrue($user->role()->equals($role));
        $this->assertEquals([], $user->scopes()->toArray());
        $this->assertTrue($user->isActive());
        $this->assertEquals($timestamp, $user->createdAt());
        $this->assertEquals($timestamp, $user->updatedAt());
    }

    public function testCreateUserWithScopes(): void
    {
        $id = UserId::fromString('user-123');
        $role = HierarchyRole::user();
        $scopes = ScopeSet::fromScopes([
            Scope::fromString('users.read'),
            Scope::fromString('tasks.write'),
        ]);
        $timestamp = 1640000000;
        
        $user = User::create($id, $role, $scopes, $timestamp);
        
        $this->assertEquals(['tasks.write', 'users.read'], $user->scopes()->toArray());
    }

    public function testAssignRole(): void
    {
        $id = UserId::fromString('user-123');
        $role = HierarchyRole::user();
        $scopes = ScopeSet::fromScopes([]);
        $timestamp = 1640000000;
        
        $user = User::create($id, $role, $scopes, $timestamp);
        
        $newRole = HierarchyRole::admin();
        $newTimestamp = 1640000100;
        $user->assignRole($newRole, $newTimestamp);
        
        $this->assertTrue($user->role()->equals($newRole));
        $this->assertEquals($newTimestamp, $user->updatedAt());
        $this->assertEquals($timestamp, $user->createdAt());
    }

    public function testAssignScopes(): void
    {
        $id = UserId::fromString('user-123');
        $role = HierarchyRole::user();
        $scopes = ScopeSet::fromScopes([
            Scope::fromString('users.read'),
        ]);
        $timestamp = 1640000000;
        
        $user = User::create($id, $role, $scopes, $timestamp);
        
        $newScopes = ScopeSet::fromScopes([
            Scope::fromString('users.read'),
            Scope::fromString('users.write'),
            Scope::fromString('tasks.read'),
        ]);
        $newTimestamp = 1640000100;
        $user->assignScopes($newScopes, $newTimestamp);
        
        $this->assertEquals(['tasks.read', 'users.read', 'users.write'], $user->scopes()->toArray());
        $this->assertEquals($newTimestamp, $user->updatedAt());
        $this->assertEquals($timestamp, $user->createdAt());
    }

    public function testDeactivate(): void
    {
        $id = UserId::fromString('user-123');
        $role = HierarchyRole::user();
        $scopes = ScopeSet::fromScopes([]);
        $timestamp = 1640000000;
        
        $user = User::create($id, $role, $scopes, $timestamp);
        
        $this->assertTrue($user->isActive());
        
        $deactivateTimestamp = 1640000100;
        $user->deactivate($deactivateTimestamp);
        
        $this->assertTrue(!$user->isActive());
        $this->assertEquals($deactivateTimestamp, $user->updatedAt());
        $this->assertEquals($timestamp, $user->createdAt());
    }

    public function testMultipleOperationsUpdateTimestamp(): void
    {
        $id = UserId::fromString('user-123');
        $role = HierarchyRole::user();
        $scopes = ScopeSet::fromScopes([]);
        $timestamp = 1640000000;
        
        $user = User::create($id, $role, $scopes, $timestamp);
        
        $user->assignRole(HierarchyRole::admin(), 1640000100);
        $this->assertEquals(1640000100, $user->updatedAt());
        
        $user->assignScopes(ScopeSet::fromScopes([Scope::fromString('admin')]), 1640000200);
        $this->assertEquals(1640000200, $user->updatedAt());
        
        $user->deactivate(1640000300);
        $this->assertEquals(1640000300, $user->updatedAt());
        
        // createdAt never changes
        $this->assertEquals($timestamp, $user->createdAt());
    }
}
