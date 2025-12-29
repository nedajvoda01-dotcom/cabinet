<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Shared\Exceptions;

final class InvalidHierarchyRole extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self("Invalid hierarchy role: {$value}");
    }
}
