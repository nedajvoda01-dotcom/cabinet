<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Retry;

use Backend\Application\Contracts\Error;
use Backend\Application\Contracts\ErrorKind;

final class RetryPolicy
{
    /**
     * @param int[] $backoffSeconds
     */
    public function __construct(
        private ErrorClassifier $classifier,
        private int $maxAttempts = 3,
        private array $backoffSeconds = [60, 300, 900],
    ) {
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function shouldRetry(int $attempts, ErrorKind $kind): bool
    {
        if (!$this->classifier->isRetryable($kind)) {
            return false;
        }

        return $attempts < $this->maxAttempts;
    }

    public function classifyAndDecide(Error $error, int $attempts): bool
    {
        $kind = $this->classifier->classify($error);

        return $this->shouldRetry($attempts, $kind);
    }

    public function nextRetryAt(int $attempt, ?int $nowTs = null): string
    {
        $nowTs ??= time();
        $idx = max(0, min($attempt - 1, count($this->backoffSeconds) - 1));
        $delay = $this->backoffSeconds[$idx] ?? end($this->backoffSeconds);

        return date('Y-m-d H:i:s', $nowTs + $delay);
    }
}
