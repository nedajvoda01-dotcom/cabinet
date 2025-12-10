<?php
// backend/src/Adapters/Ports/PhotoProcessorPort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface PhotoProcessorPort
{
    public function maskPhoto(string $rawUrl, array $maskParams = [], ?string $idempotencyKey = null): array;

    public function health(): array;
}
