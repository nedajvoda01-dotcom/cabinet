<?php
// tests/unit/backend/containerPorts.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Server\Container;
use App\Adapters\Ports\StoragePort;
use App\Adapters\Ports\ParserPort;
use App\Adapters\Ports\PhotoProcessorPort;
use App\Adapters\Ports\RobotPort;
use App\Adapters\Ports\RobotProfilePort;
use App\Adapters\Ports\MarketplacePort;
use App\Adapters\S3StorageAdapter;
use App\Adapters\ParserAdapter;
use App\Adapters\PhotoProcessorAdapter;
use App\Adapters\RobotApiAdapter;
use App\Adapters\DolphinProfileAdapter;
use App\Adapters\AvitoMarketplaceAdapter;

define('APP_ROOT', realpath(__DIR__ . '/../../..'));

final class ContainerPortsTest extends TestCase
{
    public function test_ports_bind_to_concrete_adapters(): void
    {
        $container = new Container($this->config());

        $this->assertInstanceOf(S3StorageAdapter::class, $container->get(StoragePort::class));
        $this->assertInstanceOf(ParserAdapter::class, $container->get(ParserPort::class));
        $this->assertInstanceOf(PhotoProcessorAdapter::class, $container->get(PhotoProcessorPort::class));
        $this->assertInstanceOf(RobotApiAdapter::class, $container->get(RobotPort::class));
        $this->assertInstanceOf(DolphinProfileAdapter::class, $container->get(RobotProfilePort::class));
        $this->assertInstanceOf(AvitoMarketplaceAdapter::class, $container->get(MarketplacePort::class));
    }

    private function config(): array
    {
        return [
            'db' => [
                'dsn' => 'sqlite::memory:',
                'user' => null,
                'pass' => null,
            ],
            'auth' => ['jwt_secret' => 'secret'],
            'integrations' => [
                'storage' => [
                    'bucket' => 'test-bucket',
                    'endpoint' => 'http://localhost:9000',
                    'access_key' => 'ak',
                    'secret_key' => 'sk',
                    'region' => 'us-east-1',
                    'fs_root' => sys_get_temp_dir(),
                    'path_style' => true,
                ],
                'parser' => [
                    'base_url' => 'http://parser',
                    'api_key' => 'k',
                ],
                'photo_api' => [
                    'base_url' => 'http://photo',
                    'api_key' => 'k',
                ],
                'dolphin' => [
                    'base_url' => 'http://dolphin',
                    'api_key' => 'k',
                ],
                'robot' => [
                    'base_url' => 'http://robot',
                    'api_key' => 'k',
                ],
            ],
            'workers' => [
                'id' => 'w1',
            ],
        ];
    }
}
