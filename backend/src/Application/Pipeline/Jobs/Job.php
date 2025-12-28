<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Jobs;

use Backend\Application\Contracts\TraceContext;

final class Job
{
    public function __construct(
        private string $type,
        private string $subjectType,
        private int|string $subjectId,
        private JobPayload $payload,
        private string $idempotencyKey,
        private string $traceId,
        private int $attempt = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function create(
        string $type,
        string $subjectType,
        int|string $subjectId,
        array $payload = [],
        ?string $idempotencyKey = null,
        ?string $traceId = null,
        int $attempt = 0,
    ): self {
        $trace = $traceId ?: TraceContext::ensure()->traceId();
        $idempotency = $idempotencyKey ?: self::buildIdempotencyKey($type, $subjectType, (string)$subjectId, $payload);
        $payloadObj = JobPayload::fromArray($payload, $trace, $idempotency);

        return new self($type, $subjectType, $subjectId, $payloadObj, $idempotency, $trace, $attempt);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function buildIdempotencyKey(string $type, string $subjectType, string $subjectId, array $payload): string
    {
        $correlation = (string)($payload['correlation_id'] ?? 'nocorrelation');

        return "{$type}:{$subjectType}:{$subjectId}:{$correlation}";
    }

    public function type(): string
    {
        return $this->type;
    }

    public function subjectType(): string
    {
        return $this->subjectType;
    }

    public function subjectId(): int|string
    {
        return $this->subjectId;
    }

    public function payload(): JobPayload
    {
        return $this->payload;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function attempt(): int
    {
        return $this->attempt;
    }
}
