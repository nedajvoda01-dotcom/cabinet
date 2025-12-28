<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

final class Scope
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
        if ($value !== strtolower($value)) {
            throw new \InvalidArgumentException('Value must be lowercase.');
        }
        $segments = explode(".", $value);
        foreach ($segments as $segment) {
            if ($segment === "") {
                throw new \InvalidArgumentException('Scope segments must be non-empty.');
            }
        }
        foreach (explode(".", $value) as $segment) {
            if (!preg_match("/^[a-z0-9]+$/", $segment)) {
                throw new \InvalidArgumentException('Scope segments must use [a-z0-9].');
            }
        }
    }
}
