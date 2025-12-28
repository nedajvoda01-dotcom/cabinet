<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Shared;

/**
 * @template T
 */
final class Result
{
    /** @var T|null */
    private mixed $value;

    private ?ApplicationError $error;

    /**
     * @param T|null $value
     */
    private function __construct(mixed $value, ?ApplicationError $error)
    {
        $this->value = $value;
        $this->error = $error;
    }

    /**
     * @template U
     * @param U $value
     * @return Result<U>
     */
    public static function success(mixed $value): self
    {
        return new self($value, null);
    }

    /**
     * @template U
     * @return Result<U>
     */
    public static function failure(ApplicationError $error): self
    {
        return new self(null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function isFailure(): bool
    {
        return $this->error !== null;
    }

    /**
     * @return T
     */
    public function value(): mixed
    {
        if ($this->error !== null) {
            throw new \LogicException('Cannot get value from a failed result');
        }

        return $this->value;
    }

    public function error(): ApplicationError
    {
        if ($this->error === null) {
            throw new \LogicException('Cannot get error from a successful result');
        }

        return $this->error;
    }
}
