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

    /**
     * Классификация ошибок для ретраев: network/timeout/5xx → retry.
     * contract_mismatch/explicit fatal → не retry.
     *
     * @param array{code?:string,message?:string,meta?:array,fatal?:bool} $error
     */
    public function isRetryableError(array $error): bool
    {
        if (!empty($error['fatal'])) {
            return false;
        }

        $code = (string)($error['code'] ?? '');
        if ($code === 'contract_mismatch') {
            return false;
        }

        $status = $error['meta']['status'] ?? null;
        if (is_int($status) && $status >= 500) {
            return true;
        }

        if (str_contains($code, 'timeout') || $code === 'network_error') {
            return true;
        }

        return true;
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
