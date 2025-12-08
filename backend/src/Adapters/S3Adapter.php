<?php
// backend/src/Adapters/S3Adapter.php

namespace App\Adapters;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

final class S3Adapter
{
    private ?S3Client $client = null;
    private bool $useFsFallback = false;

    public function __construct(
        private string $bucket,
        private string $endpoint,
        private string $accessKey,
        private string $secretKey,
        private string $region = 'us-east-1',
        private ?string $fsRoot = null, // e.g. /data/storage
        private bool $usePathStyle = true
    ) {
        if (class_exists(S3Client::class)) {
            $this->client = new S3Client([
                'version' => 'latest',
                'region' => $this->region,
                'endpoint' => $this->endpoint,
                'use_path_style_endpoint' => $this->usePathStyle,
                'credentials' => [
                    'key' => $this->accessKey,
                    'secret' => $this->secretKey,
                ],
            ]);
        } else {
            $this->useFsFallback = true;
            if (!$this->fsRoot) {
                throw new AdapterException("AWS SDK missing and fsRoot not configured", "s3_no_sdk", false);
            }
        }
    }

    public function putObject(string $key, string $binary, string $contentType = 'application/octet-stream'): void
    {
        if ($this->useFsFallback) {
            $path = rtrim($this->fsRoot, '/') . '/' . ltrim($key, '/');
            @mkdir(dirname($path), 0777, true);
            file_put_contents($path, $binary);
            return;
        }

        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $binary,
                'ContentType' => $contentType,
            ]);
        } catch (AwsException $e) {
            throw new AdapterException("S3 putObject failed", "s3_put_failed", true, ['key'=>$key], 0, $e);
        }
    }

    public function getObject(string $key): string
    {
        if ($this->useFsFallback) {
            $path = rtrim($this->fsRoot, '/') . '/' . ltrim($key, '/');
            if (!is_file($path)) {
                throw new AdapterException("FS object not found: {$key}", "fs_not_found", false);
            }
            return file_get_contents($path);
        }

        try {
            $res = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            return (string)$res['Body'];
        } catch (AwsException $e) {
            throw new AdapterException("S3 getObject failed", "s3_get_failed", true, ['key'=>$key], 0, $e);
        }
    }

    public function presignGet(string $key, int $expiresSec = 3600): string
    {
        if ($this->useFsFallback) {
            return $this->publicUrl($key);
        }

        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);
        $req = $this->client->createPresignedRequest($cmd, "+{$expiresSec} seconds");
        return (string)$req->getUri();
    }

    public function listPrefix(string $prefix): array
    {
        if ($this->useFsFallback) {
            $root = rtrim($this->fsRoot, '/') . '/' . trim($prefix, '/');
            if (!is_dir($root)) return [];
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
            $out = [];
            foreach ($rii as $file) {
                if ($file->isDir()) continue;
                $out[] = str_replace(rtrim($this->fsRoot, '/') . '/', '', $file->getPathname());
            }
            return $out;
        }

        try {
            $res = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);
            $out = [];
            foreach (($res['Contents'] ?? []) as $item) {
                $out[] = $item['Key'];
            }
            return $out;
        } catch (AwsException $e) {
            throw new AdapterException("S3 list failed", "s3_list_failed", true, ['prefix'=>$prefix], 0, $e);
        }
    }

    public function publicUrl(string $key): string
    {
        // public-style URL (works for MinIO behind nginx too)
        return rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . ltrim($key, '/');
    }
}
