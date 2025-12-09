<?php
// backend/src/Adapters/Ports/RobotPort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface RobotPort
{
    public function start(array $profile): array;

    public function publish(string $sessionId, array $avitoPayload): array;

    public function pollStatus(string $avitoItemId): array;

    public function stop(string $sessionId): void;

    public function health(): array;
}
