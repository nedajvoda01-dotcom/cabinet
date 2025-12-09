<?php
// backend/src/Adapters/Ports/ParserPort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface ParserPort
{
    public function normalizePush(array $push): array;

    public function poll(int $limit = 20): array;

    public function ack(string $externalId, array $meta = []): void;

    public function downloadBinary(string $url): string;

    public function uploadRaw(string $key, string $binary, string $extension): string;

    public function publicUrl(string $key): string;

    public function guessExt(string $url): ?string;
}
