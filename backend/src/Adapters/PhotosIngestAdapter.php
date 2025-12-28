<?php
// backend/src/Adapters/PhotosIngestAdapter.php

declare(strict_types=1);

namespace App\Adapters;

use App\Adapters\AdapterException;
use App\Adapters\HttpClient;
use App\Adapters\S3Adapter;
use App\Application\Ports\PhotosIngestPort;

final class PhotosIngestAdapter implements PhotosIngestPort
{
    public function __construct(private HttpClient $http, private S3Adapter $s3) {}

    public function download(string $url): string
    {
        $resp = $this->http->get($url);

        if ($resp['status'] >= 400) {
            throw new AdapterException(
                "Photo download failed: {$url}",
                'parser_photo_download',
                true,
                ['url' => $url, 'status' => $resp['status']]
            );
        }

        return (string)$resp['raw'];
    }

    public function storeRaw(string $key, string $binary, string $extension): string
    {
        $this->s3->putObject($key, $binary, "image/{$extension}");

        return $this->s3->publicUrl($key);
    }
}
