<?php
// backend/src/Adapters/Ports/RobotProfilePort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface RobotProfilePort
{
    public function allocateProfile(array $cardSnapshot): array;

    public function startProfile(string $profileId): array;

    public function stopProfile(string $profileId): void;

    public function health(): array;
}
