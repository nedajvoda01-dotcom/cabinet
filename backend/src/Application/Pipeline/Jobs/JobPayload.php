<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Jobs;

final class JobPayload
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(private array $data)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $traceId, string $idempotencyKey): self
    {
        $data['trace_id'] = $data['trace_id'] ?? $traceId;
        $data['idempotency_key'] = $data['idempotency_key'] ?? $idempotencyKey;

        return new self($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function traceId(): string
    {
        return (string)($this->data['trace_id'] ?? '');
    }

    public function idempotencyKey(): string
    {
        return (string)($this->data['idempotency_key'] ?? '');
    }
}
