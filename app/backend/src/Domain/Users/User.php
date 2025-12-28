<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Users;

use Cabinet\Backend\Domain\Shared\ValueObject\HierarchyRole;
use Cabinet\Backend\Domain\Shared\ValueObject\ScopeSet;

final class User
{
    private HierarchyRole $role;
    private ScopeSet $scopes;
    private bool $isActive;
    private int $createdAt;
    private int $updatedAt;

    private function __construct(
        private readonly UserId $id,
        HierarchyRole $role,
        ScopeSet $scopes,
        bool $isActive,
        int $createdAt,
        int $updatedAt
    ) {
        $this->role = $role;
        $this->scopes = $scopes;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        UserId $id,
        HierarchyRole $role,
        ScopeSet $scopes,
        int $timestamp
    ): self {
        return new self($id, $role, $scopes, true, $timestamp, $timestamp);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function role(): HierarchyRole
    {
        return $this->role;
    }

    public function scopes(): ScopeSet
    {
        return $this->scopes;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): int
    {
        return $this->createdAt;
    }

    public function updatedAt(): int
    {
        return $this->updatedAt;
    }

    public function assignRole(HierarchyRole $role, int $timestamp): void
    {
        $this->role = $role;
        $this->updatedAt = $timestamp;
    }

    public function assignScopes(ScopeSet $scopes, int $timestamp): void
    {
        $this->scopes = $scopes;
        $this->updatedAt = $timestamp;
    }

    public function deactivate(int $timestamp): void
    {
        $this->isActive = false;
        $this->updatedAt = $timestamp;
    }
}
