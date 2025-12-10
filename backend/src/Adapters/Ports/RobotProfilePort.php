<?php
// backend/src/Adapters/Ports/RobotProfilePort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface RobotProfilePort
{
    public function allocateProfile(array $cardSnapshot, ?string $idempotencyKey = null): array;

    public function startProfile(string $profileId, ?string $idempotencyKey = null): array;

    public function stopProfile(string $profileId, ?string $idempotencyKey = null): void;

    public function health(): array;
}
