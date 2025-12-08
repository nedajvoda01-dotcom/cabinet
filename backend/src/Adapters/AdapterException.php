<?php
// backend/src/Adapters/AdapterException.php

namespace App\Adapters;

use RuntimeException;

final class AdapterException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $codeStr = null,
        public readonly bool $retryable = true,
        public readonly array $meta = [],
        int $code = 0,
        ?\Throwable $prev = null
    ) {
        parent::__construct($message, $code, $prev);
    }

    public function toErrorArray(): array
    {
        return [
            'code' => $this->codeStr,
            'message' => $this->getMessage(),
            'meta' => $this->meta,
            'fatal' => !$this->retryable,
        ];
    }
}
