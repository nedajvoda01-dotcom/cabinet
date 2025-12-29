<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Shared\ValueObject\Scope;
use Cabinet\Backend\Domain\Shared\ValueObject\ScopeSet;
use Cabinet\Backend\Tests\TestCase;

final class ScopeSetTest extends TestCase
{
    public function testEmptySet(): void
    {
        $set = ScopeSet::fromScopes([]);
        $this->assertEquals([], $set->toArray());
    }

    public function testSingleScope(): void
    {
        $scope = Scope::fromString('users.read');
        $set = ScopeSet::fromScopes([$scope]);
        
        $this->assertEquals(['users.read'], $set->toArray());
        $this->assertTrue($set->has($scope));
    }

    public function testMultipleScopes(): void
    {
        $scope1 = Scope::fromString('users.read');
        $scope2 = Scope::fromString('users.write');
        $scope3 = Scope::fromString('tasks.read');
        
        $set = ScopeSet::fromScopes([$scope1, $scope2, $scope3]);
        
        $this->assertTrue($set->has($scope1));
        $this->assertTrue($set->has($scope2));
        $this->assertTrue($set->has($scope3));
    }

    public function testDeterministicOrdering(): void
    {
        $scope1 = Scope::fromString('zebra');
        $scope2 = Scope::fromString('alpha');
        $scope3 = Scope::fromString('beta');
        
        $set = ScopeSet::fromScopes([$scope1, $scope2, $scope3]);
        
        // Should be sorted alphabetically
        $this->assertEquals(['alpha', 'beta', 'zebra'], $set->toArray());
    }

    public function testDuplicatesRemoved(): void
    {
        $scope1 = Scope::fromString('users.read');
        $scope2 = Scope::fromString('users.read');
        
        $set = ScopeSet::fromScopes([$scope1, $scope2]);
        
        $this->assertEquals(['users.read'], $set->toArray());
    }

    public function testHasReturnsFalseForMissingScope(): void
    {
        $scope1 = Scope::fromString('users.read');
        $scope2 = Scope::fromString('users.write');
        
        $set = ScopeSet::fromScopes([$scope1]);
        
        $this->assertTrue($set->has($scope1));
        $this->assertTrue(!$set->has($scope2));
    }

    public function testHasAll(): void
    {
        $scope1 = Scope::fromString('users.read');
        $scope2 = Scope::fromString('users.write');
        $scope3 = Scope::fromString('tasks.read');
        
        $set = ScopeSet::fromScopes([$scope1, $scope2, $scope3]);
        
        $this->assertTrue($set->hasAll([$scope1, $scope2]));
        $this->assertTrue($set->hasAll([$scope1]));
        $this->assertTrue($set->hasAll([]));
    }

    public function testHasAllReturnsFalseWhenMissing(): void
    {
        $scope1 = Scope::fromString('users.read');
        $scope2 = Scope::fromString('users.write');
        $scope3 = Scope::fromString('tasks.read');
        
        $set = ScopeSet::fromScopes([$scope1, $scope2]);
        
        $this->assertTrue(!$set->hasAll([$scope1, $scope2, $scope3]));
    }
}
