<?php
// backend/src/Application/Ports/PhotosIngestPort.php

declare(strict_types=1);

namespace App\Application\Ports;

interface PhotosIngestPort
{
    /**
     * Download photo bytes from a remote URL.
     */
    public function download(string $url): string;

    /**
     * Store raw photo bytes under the provided key and return a public URL.
     */
    public function storeRaw(string $key, string $binary, string $extension): string;
}
