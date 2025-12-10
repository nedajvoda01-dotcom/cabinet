<?php
// tests/unit/backend/containerFakeMode.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Server\Container;
use App\Adapters\Ports\StoragePort;
use App\Adapters\Ports\ParserPort;
use App\Adapters\Ports\PhotoProcessorPort;
use App\Adapters\Ports\RobotPort;
use App\Adapters\Ports\RobotProfilePort;
use App\Adapters\Ports\MarketplacePort;
use App\Adapters\Fakes\FakeStorageAdapter;
use App\Adapters\Fakes\FakeParserAdapter;
use App\Adapters\Fakes\FakePhotoProcessorAdapter;
use App\Adapters\Fakes\FakeRobotApiAdapter;
use App\Adapters\Fakes\FakeRobotProfileAdapter;
use App\Adapters\Fakes\FakeMarketplaceAdapter;
use App\Adapters\S3StorageAdapter;
use App\Adapters\ParserAdapter;
use App\Adapters\PhotoProcessorAdapter;
use App\Adapters\RobotApiAdapter;
use App\Adapters\DolphinProfileAdapter;
use App\Adapters\AvitoMarketplaceAdapter;

final class ContainerFakeModeTest extends TestCase
{
    private ?string $prevMode = null;

    protected function setUp(): void
    {
        $this->prevMode = getenv('INTEGRATIONS_MODE') ?: null;
    }

    protected function tearDown(): void
    {
        if ($this->prevMode === null) {
            putenv('INTEGRATIONS_MODE');
        } else {
            putenv('INTEGRATIONS_MODE=' . $this->prevMode);
        }
    }

    public function test_fake_mode_resolves_fake_adapters(): void
    {
        putenv('INTEGRATIONS_MODE=fake');
        $container = new Container($this->config());

        $this->assertInstanceOf(FakeStorageAdapter::class, $container->get(StoragePort::class));
        $this->assertInstanceOf(FakeParserAdapter::class, $container->get(ParserPort::class));
        $this->assertInstanceOf(FakePhotoProcessorAdapter::class, $container->get(PhotoProcessorPort::class));
        $this->assertInstanceOf(FakeRobotApiAdapter::class, $container->get(RobotPort::class));
        $this->assertInstanceOf(FakeRobotProfileAdapter::class, $container->get(RobotProfilePort::class));
        $this->assertInstanceOf(FakeMarketplaceAdapter::class, $container->get(MarketplacePort::class));
    }

    public function test_real_mode_resolves_real_adapters(): void
    {
        putenv('INTEGRATIONS_MODE=real');
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
            'integrations_mode' => getenv('INTEGRATIONS_MODE') ?: 'real',
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
            'ws' => ['enabled' => false],
            'workers' => [
                'id' => 'w1',
                'sleep_ms' => 1,
            ],
        ];
    }
}
