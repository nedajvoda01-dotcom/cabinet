<?php
// backend/src/Adapters/Fakes/FakeMarketplaceAdapter.php

declare(strict_types=1);

namespace App\Adapters\Fakes;

use App\Adapters\AvitoMarketplaceAdapter;
use App\Adapters\Ports\MarketplacePort;

final class FakeMarketplaceAdapter implements MarketplacePort
{
    private AvitoMarketplaceAdapter $mapper;

    public function __construct()
    {
        $this->mapper = new AvitoMarketplaceAdapter();
    }

    public function mapCard(array $card): array
    {
        if (empty($card)) {
            $card = [
                'vehicle' => ['make' => 'Audi', 'model' => 'A6', 'year' => 2018],
                'price' => ['value' => 2350000, 'currency' => 'RUB'],
                'description' => 'Fake card',
                'photos' => [
                    ['masked_url' => 'http://storage.local/masked/a6/0.jpg', 'order' => 0],
                ],
                'location' => ['city' => 'Москва'],
            ];
        }

        return $this->mapper->mapCard($card);
    }

    public function normalizeStatus(string $externalStatus): string
    {
        return $this->mapper->normalizeStatus($externalStatus);
    }
}
