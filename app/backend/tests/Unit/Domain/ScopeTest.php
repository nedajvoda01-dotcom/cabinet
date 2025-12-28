<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidScopeFormat;
use Cabinet\Backend\Domain\Shared\ValueObject\Scope;
use Cabinet\Backend\Tests\TestCase;

final class ScopeTest extends TestCase
{
    public function testValidScope(): void
    {
        $scope = Scope::fromString('users.read');
        $this->assertEquals('users.read', $scope->toString());
    }

    public function testValidScopeSingleSegment(): void
    {
        $scope = Scope::fromString('admin');
        $this->assertEquals('admin', $scope->toString());
    }

    public function testValidScopeMultipleSegments(): void
    {
        $scope = Scope::fromString('api.tasks.write.all');
        $this->assertEquals('api.tasks.write.all', $scope->toString());
    }

    public function testScopeEquals(): void
    {
        $scope1 = Scope::fromString('users.read');
        $scope2 = Scope::fromString('users.read');
        $scope3 = Scope::fromString('users.write');
        
        $this->assertTrue($scope1->equals($scope2), 'Same scopes should be equal');
        $this->assertTrue(!$scope1->equals($scope3), 'Different scopes should not be equal');
    }

    public function testScopeRejectsEmpty(): void
    {
        try {
            Scope::fromString('');
            throw new \Exception('Should have thrown InvalidScopeFormat');
        } catch (InvalidScopeFormat $e) {
            $this->assertTrue(true);
        }
    }

    public function testScopeRejectsUppercase(): void
    {
        try {
            Scope::fromString('Users.Read');
            throw new \Exception('Should have thrown InvalidScopeFormat');
        } catch (InvalidScopeFormat $e) {
            $this->assertTrue(true);
        }
    }

    public function testScopeRejectsEmptySegment(): void
    {
        try {
            Scope::fromString('users..read');
            throw new \Exception('Should have thrown InvalidScopeFormat');
        } catch (InvalidScopeFormat $e) {
            $this->assertTrue(true);
        }
    }

    public function testScopeRejectsLeadingDot(): void
    {
        try {
            Scope::fromString('.users.read');
            throw new \Exception('Should have thrown InvalidScopeFormat');
        } catch (InvalidScopeFormat $e) {
            $this->assertTrue(true);
        }
    }

    public function testScopeRejectsTrailingDot(): void
    {
        try {
            Scope::fromString('users.read.');
            throw new \Exception('Should have thrown InvalidScopeFormat');
        } catch (InvalidScopeFormat $e) {
            $this->assertTrue(true);
        }
    }
}
