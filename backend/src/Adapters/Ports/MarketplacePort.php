<?php
// backend/src/Adapters/Ports/MarketplacePort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface MarketplacePort
{
    public function mapCard(array $card): array;

    public function normalizeStatus(string $externalStatus): string;
}
