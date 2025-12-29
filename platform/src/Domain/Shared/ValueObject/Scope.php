<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Shared\ValueObject;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidScopeFormat;

final class Scope
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw InvalidScopeFormat::forValue($value);
        }

        // Must be lowercase dot-separated segments
        if ($value !== strtolower($value)) {
            throw InvalidScopeFormat::forValue($value);
        }

        // Split by dots and validate segments
        $segments = explode('.', $value);
        foreach ($segments as $segment) {
            if ($segment === '') {
                throw InvalidScopeFormat::forValue($value);
            }
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(Scope $other): bool
    {
        return $this->value === $other->value;
    }
}
