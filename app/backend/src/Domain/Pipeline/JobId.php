<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Pipeline;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidIdentifier;

final class JobId
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if ($value === '' || strlen($value) > 255) {
            throw InvalidIdentifier::forValue($value);
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(JobId $other): bool
    {
        return $this->value === $other->value;
    }
}
