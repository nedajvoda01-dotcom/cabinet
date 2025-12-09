<?php
// backend/src/Adapters/Ports/StoragePort.php

declare(strict_types=1);

namespace App\Adapters\Ports;

interface StoragePort
{
    public function putObject(string $key, string $binary, string $contentType = 'application/octet-stream'): void;

    public function getObject(string $key): string;

    public function presignGet(string $key, int $expiresSec = 3600): string;

    public function listPrefix(string $prefix): array;

    public function publicUrl(string $key): string;
}
