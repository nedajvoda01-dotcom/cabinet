<?php
// backend/src/Adapters/Ports/RobotPort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface RobotPort
{
    public function start(array $profile, ?string $idempotencyKey = null): array;

    public function publish(string $sessionId, array $avitoPayload, ?string $idempotencyKey = null): array;

    public function pollStatus(string $avitoItemId, ?string $idempotencyKey = null): array;

    public function stop(string $sessionId, ?string $idempotencyKey = null): void;

    public function health(): array;
}
