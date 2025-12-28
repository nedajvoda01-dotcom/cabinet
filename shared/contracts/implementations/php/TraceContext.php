<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

final class TraceContext
{
    public function __construct(public readonly string $requestId, public readonly ?string $timestamp)
    {
        if ($requestId === null) {
            throw new \InvalidArgumentException('requestId is required.');
        }
        if ($requestId === '') {
            throw new \InvalidArgumentException('requestId must be non-empty.');
        }
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->requestId !== null) {
            $data['requestId'] = $this->requestId;
        }
        if ($this->timestamp !== null) {
            $data['timestamp'] = $this->timestamp;
        }
        return $data;
    }
}
