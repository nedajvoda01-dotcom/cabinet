<?php
declare(strict_types=1);

namespace Backend\Application\Contracts;

final class TraceContext
{
    private static ?TraceContext $current = null;

    public function __construct(private string $traceId)
    {
    }

    public static function generateTraceId(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable) {
            return uniqid('trace_', true);
        }
    }

    public static function generate(): self
    {
        return new self(self::generateTraceId());
    }

    public static function ensure(): self
    {
        if (!self::$current) {
            self::$current = self::generate();
        }

        return self::$current;
    }

    public static function setCurrent(self $context): void
    {
        self::$current = $context;
    }

    public static function fromString(string $traceId): self
    {
        return new self($traceId);
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function toArray(): array
    {
        return ['traceId' => $this->traceId];
    }
}
