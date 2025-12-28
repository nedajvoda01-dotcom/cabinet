<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

final class ActorId
{
    public function __construct(private readonly string $value)
    {
        $this->assert($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    private function assert(string $value): void
    {
        if ($value === "") {
            throw new \InvalidArgumentException('Value must be non-empty.');
        }
        if (!mb_check_encoding($value, "ASCII")) {
            throw new \InvalidArgumentException('Value must be ASCII.');
        }
        if (mb_strlen($value) > 128) {
            throw new \InvalidArgumentException('Value exceeds maximum length of 128.');
        }
    }
}
