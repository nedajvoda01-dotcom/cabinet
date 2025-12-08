<?php
// backend/src/Queues/RetryPolicy.php

namespace App\Queues;

final class RetryPolicy
{
    /** @var int Максимум попыток до DLQ */
    private int $maxAttempts;

    /** @var int[] секундные интервалы backoff по попытке (1-based) */
    private array $backoff;

    public function __construct(
        int $maxAttempts = 5,
        array $backoffSeconds = [60, 300, 900, 3600, 7200] // 1m,5m,15m,1h,2h
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->backoff = $backoffSeconds;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function shouldRetry(int $attempts): bool
    {
        return $attempts < $this->maxAttempts;
    }

    public function nextRetryAt(int $attempts, ?int $nowTs = null): string
    {
        $nowTs ??= time();
        $idx = max(0, min($attempts - 1, count($this->backoff) - 1));
        $delay = $this->backoff[$idx] ?? end($this->backoff);
        return date('Y-m-d H:i:s', $nowTs + $delay);
    }
}
