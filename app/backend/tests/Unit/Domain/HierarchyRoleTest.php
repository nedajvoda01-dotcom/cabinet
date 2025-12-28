<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidHierarchyRole;
use Cabinet\Backend\Domain\Shared\ValueObject\HierarchyRole;
use Cabinet\Backend\Tests\TestCase;

final class HierarchyRoleTest extends TestCase
{
    public function testFromStringUser(): void
    {
        $role = HierarchyRole::fromString('user');
        $this->assertEquals('user', $role->toString());
    }

    public function testFromStringAdmin(): void
    {
        $role = HierarchyRole::fromString('admin');
        $this->assertEquals('admin', $role->toString());
    }

    public function testFromStringSuperAdmin(): void
    {
        $role = HierarchyRole::fromString('super_admin');
        $this->assertEquals('super_admin', $role->toString());
    }

    public function testStaticConstructors(): void
    {
        $user = HierarchyRole::user();
        $admin = HierarchyRole::admin();
        $superAdmin = HierarchyRole::superAdmin();
        
        $this->assertEquals('user', $user->toString());
        $this->assertEquals('admin', $admin->toString());
        $this->assertEquals('super_admin', $superAdmin->toString());
    }

    public function testFromStringRejectsInvalid(): void
    {
        try {
            HierarchyRole::fromString('invalid');
            throw new \Exception('Should have thrown InvalidHierarchyRole');
        } catch (InvalidHierarchyRole $e) {
            $this->assertTrue(true);
        }
    }

    public function testEquals(): void
    {
        $user1 = HierarchyRole::user();
        $user2 = HierarchyRole::user();
        $admin = HierarchyRole::admin();
        
        $this->assertTrue($user1->equals($user2));
        $this->assertTrue(!$user1->equals($admin));
    }

    public function testCompareTo(): void
    {
        $user = HierarchyRole::user();
        $admin = HierarchyRole::admin();
        $superAdmin = HierarchyRole::superAdmin();
        
        // user < admin
        $this->assertEquals(-1, $user->compareTo($admin));
        $this->assertEquals(1, $admin->compareTo($user));
        
        // admin < super_admin
        $this->assertEquals(-1, $admin->compareTo($superAdmin));
        $this->assertEquals(1, $superAdmin->compareTo($admin));
        
        // user < super_admin
        $this->assertEquals(-1, $user->compareTo($superAdmin));
        $this->assertEquals(1, $superAdmin->compareTo($user));
        
        // Equal comparison
        $this->assertEquals(0, $user->compareTo($user));
        $this->assertEquals(0, $admin->compareTo($admin));
    }

    public function testIsAtLeast(): void
    {
        $user = HierarchyRole::user();
        $admin = HierarchyRole::admin();
        $superAdmin = HierarchyRole::superAdmin();
        
        // user is at least user
        $this->assertTrue($user->isAtLeast($user));
        
        // user is not at least admin
        $this->assertTrue(!$user->isAtLeast($admin));
        
        // admin is at least user
        $this->assertTrue($admin->isAtLeast($user));
        
        // admin is at least admin
        $this->assertTrue($admin->isAtLeast($admin));
        
        // admin is not at least super_admin
        $this->assertTrue(!$admin->isAtLeast($superAdmin));
        
        // super_admin is at least everything
        $this->assertTrue($superAdmin->isAtLeast($user));
        $this->assertTrue($superAdmin->isAtLeast($admin));
        $this->assertTrue($superAdmin->isAtLeast($superAdmin));
    }
}
