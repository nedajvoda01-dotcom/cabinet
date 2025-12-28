<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Shared\Exceptions;

final class InvalidScopeFormat extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self("Invalid scope format: {$value}");
    }
}
