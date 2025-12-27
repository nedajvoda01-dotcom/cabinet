<?php
// backend/src/Adapters/AvitoAdapter.php

namespace App\Adapters;

/**
 * Pure mapper CardSnapshot -> AvitoPayload.
 * Не ходит в сеть, не знает DB, только формат.
 */
final class AvitoAdapter
{
    public function mapCard(array $card): array
    {
        // max mapping based on Spec minimal Card + Avito rules
        $vehicle = $card['vehicle'] ?? [];
        $price = $card['price'] ?? [];
        $location = $card['location'] ?? [];

        return [
            'title' => $this->makeTitle($vehicle),
            'description' => (string)($card['description'] ?? ''),
            'price' => [
                'value' => (int)($price['value'] ?? 0),
                'currency' => (string)($price['currency'] ?? 'RUB'),
            ],
            'vehicle' => [
                'make' => (string)($vehicle['make'] ?? ''),
                'model' => (string)($vehicle['model'] ?? ''),
                'year' => (int)($vehicle['year'] ?? 0),
                'body' => (string)($vehicle['body'] ?? ''),
                'mileage' => (int)($vehicle['mileage'] ?? 0),
                'vin' => $vehicle['vin'] ?? null,
            ],
            'location' => [
                'city' => (string)($location['city'] ?? ''),
                'address' => $location['address'] ?? null,
                'coords' => $location['coords'] ?? null,
            ],
            'photos' => array_values(array_map(function($p){
                return [
                    'url' => $p['masked_url'] ?? $p['raw_url'] ?? null,
                    'order' => $p['order'] ?? null,
                ];
            }, $card['photos'] ?? [])),
            'meta' => [
                'source' => $card['source'] ?? 'auto_ru',
                'source_id' => $card['source_id'] ?? null,
            ],
        ];
    }

    public function normalizeStatus(string $avitoStatus): string
    {
        $s = strtolower($avitoStatus);
        return match($s) {
            'active', 'published' => 'published',
            'blocked', 'rejected' => 'publish_failed',
            'processing', 'moderation' => 'publish_processing',
            default => 'publish_unknown',
        };
    }

    private function makeTitle(array $vehicle): string
    {
        $make = trim((string)($vehicle['make'] ?? ''));
        $model = trim((string)($vehicle['model'] ?? ''));
        $year = (int)($vehicle['year'] ?? 0);
        $parts = array_filter([$make, $model, $year ?: null]);
        return implode(' ', $parts);
    }
}
