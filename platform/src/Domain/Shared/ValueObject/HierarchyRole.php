<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Shared\ValueObject;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidHierarchyRole;
use Cabinet\Contracts\HierarchyRole as ContractHierarchyRole;

final class HierarchyRole
{
    private function __construct(private readonly ContractHierarchyRole $role)
    {
    }

    public static function fromString(string $value): self
    {
        $contractRole = ContractHierarchyRole::tryFrom($value);
        if ($contractRole === null) {
            throw InvalidHierarchyRole::forValue($value);
        }

        return new self($contractRole);
    }

    public static function user(): self
    {
        return new self(ContractHierarchyRole::USER);
    }

    public static function admin(): self
    {
        return new self(ContractHierarchyRole::ADMIN);
    }

    public static function superAdmin(): self
    {
        return new self(ContractHierarchyRole::SUPER_ADMIN);
    }

    public function toString(): string
    {
        return $this->role->value;
    }

    /**
     * Compare this role with another. Returns:
     * -1 if this role is less than other
     * 0 if roles are equal
     * 1 if this role is greater than other
     */
    public function compareTo(HierarchyRole $other): int
    {
        $order = [
            ContractHierarchyRole::USER->value => 0,
            ContractHierarchyRole::ADMIN->value => 1,
            ContractHierarchyRole::SUPER_ADMIN->value => 2,
        ];

        $thisLevel = $order[$this->role->value];
        $otherLevel = $order[$other->role->value];

        return $thisLevel <=> $otherLevel;
    }

    /**
     * Check if this role is at least as high as the required role
     */
    public function isAtLeast(HierarchyRole $required): bool
    {
        return $this->compareTo($required) >= 0;
    }

    public function equals(HierarchyRole $other): bool
    {
        return $this->role === $other->role;
    }
}
