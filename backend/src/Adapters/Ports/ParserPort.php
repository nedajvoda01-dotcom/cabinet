<?php
// backend/src/Adapters/Ports/ParserPort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface ParserPort
{
    public function normalizePush(array $push): array;

    public function ingestRawPhotos(array $photoUrls, int $cardDraftId, ?string $idempotencyKey = null): array;

    public function poll(int $limit = 20, ?string $idempotencyKey = null): array;

    public function ack(string $externalId, array $meta = [], ?string $idempotencyKey = null): void;
}
