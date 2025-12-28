<?php
// backend/src/Adapters/Ports/ParserPort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface ParserPort
{
    public function normalizePush(array $push): array;

    /**
     * External parser API call.
     */
    public function poll(int $limit = 20, ?string $idempotencyKey = null): array;

    /**
     * External parser API call.
     */
    public function ack(string $externalId, array $meta = [], ?string $idempotencyKey = null): void;
}
