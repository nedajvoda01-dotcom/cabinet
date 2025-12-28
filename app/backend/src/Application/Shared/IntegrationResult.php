<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Shared;

use Cabinet\Contracts\ErrorKind;

final class IntegrationResult
{
    /** @var array<string, mixed> */
    private array $payload;

    private ?ErrorKind $errorKind;

    private bool $retryable;

    private bool $success;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(bool $success, array $payload, ?ErrorKind $errorKind, bool $retryable)
    {
        $this->success = $success;
        $this->payload = $payload;
        $this->errorKind = $errorKind;
        $this->retryable = $retryable;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function succeeded(array $payload = []): self
    {
        return new self(true, $payload, null, false);
    }

    public static function failed(ErrorKind $kind, bool $retryable): self
    {
        return new self(false, [], $kind, $retryable);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailed(): bool
    {
        return !$this->success;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function errorKind(): ?ErrorKind
    {
        return $this->errorKind;
    }
}
