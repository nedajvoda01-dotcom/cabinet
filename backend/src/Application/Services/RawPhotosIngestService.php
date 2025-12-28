<?php
// backend/src/Application/Services/RawPhotosIngestService.php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Ports\PhotosIngestPort;
use App\Application\Contracts\TraceContext;

final class RawPhotosIngestService
{
    public function __construct(private PhotosIngestPort $port) {}

    /**
     * Orchestrate raw photo ingestion into storage while preserving order and trace.
     *
     * @param array<int, mixed> $photoUrls
     * @return array<int, array{order:int, raw_key:string, raw_url:string}>
     */
    public function ingest(array $photoUrls, int $cardDraftId): array
    {
        TraceContext::ensure();

        $out = [];
        $order = 0;

        foreach ($photoUrls as $url) {
            $order++;
            if (!is_string($url) || $url === '') {
                continue;
            }

            $ext = $this->guessExt($url) ?? 'jpg';
            $key = "raw/{$cardDraftId}/{$order}.{$ext}";

            $binary = $this->port->download($url);
            $publicUrl = $this->port->storeRaw($key, $binary, $ext);

            $out[] = [
                'order' => $order,
                'raw_key' => $key,
                'raw_url' => $publicUrl,
            ];
        }

        return $out;
    }

    private function guessExt(string $url): ?string
    {
        $p = parse_url($url, PHP_URL_PATH);
        if (!$p) {
            return null;
        }

        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        return $ext ?: null;
    }
}
