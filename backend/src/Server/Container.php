<?php
// cabinet/backend/src/Server/Container.php

namespace Backend\Server;

use App\Adapters\Fakes\FakeMarketplaceAdapter;
use App\Adapters\Fakes\FakeParserAdapter;
use App\Adapters\Fakes\FakePhotoProcessorAdapter;
use App\Adapters\Fakes\FakeRobotApiAdapter;
use App\Adapters\Fakes\FakeRobotProfileAdapter;
use App\Adapters\Fakes\FakeStorageAdapter;
use RuntimeException;

final class Container
{
    private array $factories = [];
    private array $instances = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->bootstrapDefaults();
    }

    public function config(): array
    {
        return $this->config;
    }

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (!isset($this->factories[$id])) {
            throw new RuntimeException("Container: service not found: {$id}");
        }
        $this->instances[$id] = ($this->factories[$id])($this);
        return $this->instances[$id];
    }

    private function bootstrapDefaults(): void
    {
        $integrationsMode = strtolower((string)($this->config['integrations_mode'] ?? 'real'));
        $useFakeIntegrations = $integrationsMode === 'fake';

        // ----------------------------------------------------
        // DB PDO
        // ----------------------------------------------------
        $this->set(\PDO::class, function(Container $c) {
            $db = $c->config()['db'];
            $pdo = new \PDO($db['dsn'], $db['user'], $db['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        });

        // ----------------------------------------------------
        // Logger (DB-backed)
        // ----------------------------------------------------
        $this->set(\Backend\Logger\LoggerInterface::class, fn(Container $c) =>
            new \Backend\Logger\DbLogger($c->get(\PDO::class))
        );

        // ----------------------------------------------------
        // Queues subsystem
        // ----------------------------------------------------
        $this->set(\App\Queues\RetryPolicy::class, fn() => new \App\Queues\RetryPolicy());

        $this->set(\App\Queues\QueueRepository::class,
            fn(Container $c) => new \App\Queues\QueueRepository($c->get(\PDO::class))
        );

        $this->set(\App\Queues\DlqRepository::class,
            fn(Container $c) => new \App\Queues\DlqRepository($c->get(\PDO::class))
        );

        // ВАЖНО: QueueService теперь получает LoggerInterface
        $this->set(\App\Queues\QueueService::class,
            fn(Container $c) => new \App\Queues\QueueService(
                $c->get(\App\Queues\QueueRepository::class),
                $c->get(\App\Queues\DlqRepository::class),
                $c->get(\App\Queues\RetryPolicy::class),
                $c->get(\Backend\Logger\LoggerInterface::class),
            )
        );

        // ----------------------------------------------------
        // HttpClient + ContractValidator
        // ----------------------------------------------------
        $this->set(\App\Adapters\HttpClient::class, fn() => new \App\Adapters\HttpClient());
        $this->set(\App\Utils\ContractValidator::class, fn() => new \App\Utils\ContractValidator());

        // ----------------------------------------------------
        // Storage adapters (real + fake)
        // ----------------------------------------------------
        $this->set(\App\Adapters\S3StorageAdapter::class, function(Container $c) {
            $s = $c->config()['integrations']['storage'];
            return new \App\Adapters\S3StorageAdapter(
                $s['bucket'],
                $s['endpoint'],
                $s['access_key'],
                $s['secret_key'],
                $s['region'],
                $s['fs_root'],
                $s['path_style']
            );
        });

        // если где-то в коде ещё используется старое имя S3Adapter — оставляем биндинг
        $this->set(\App\Adapters\S3Adapter::class, function(Container $c) {
            $s = $c->config()['integrations']['storage'];
            return new \App\Adapters\S3Adapter(
                $s['bucket'],
                $s['endpoint'],
                $s['access_key'],
                $s['secret_key'],
                $s['region'],
                $s['fs_root'],
                $s['path_style']
            );
        });

        $this->set(FakeStorageAdapter::class, fn() => new FakeStorageAdapter());

        $this->set(\App\Adapters\Ports\StoragePort::class, fn(Container $c) =>
            $useFakeIntegrations
                ? $c->get(FakeStorageAdapter::class)
                : $c->get(\App\Adapters\S3StorageAdapter::class)
        );

        // ----------------------------------------------------
        // Parser adapters (real + fake)
        // ----------------------------------------------------
        $this->set(\App\Adapters\ParserAdapter::class, function(Container $c) {
            $p = $c->config()['integrations']['parser'];
            return new \App\Adapters\ParserAdapter(
                $c->get(\App\Adapters\HttpClient::class),
                $c->get(\App\Adapters\S3Adapter::class),
                $c->get(\App\Utils\ContractValidator::class),
                $p['base_url'],
                $p['api_key']
            );
        });

        $this->set(FakeParserAdapter::class, fn() => new FakeParserAdapter());

        $this->set(\App\Adapters\Ports\ParserPort::class, fn(Container $c) =>
            $useFakeIntegrations
                ? $c->get(FakeParserAdapter::class)
                : $c->get(\App\Adapters\ParserAdapter::class)
        );

        // ----------------------------------------------------
        // Photo processor adapters (real + fake)
        // ----------------------------------------------------
        $this->set(\App\Adapters\PhotoProcessorAdapter::class, function(Container $c) {
            $p = $c->config()['integrations']['photo_api'];
            return new \App\Adapters\PhotoProcessorAdapter(
                $c->get(\App\Adapters\HttpClient::class),
                $c->get(\App\Utils\ContractValidator::class),
                $p['base_url'],
                $p['api_key']
            );
        });

        $this->set(FakePhotoProcessorAdapter::class, fn() => new FakePhotoProcessorAdapter());

        $this->set(\App\Adapters\Ports\PhotoProcessorPort::class, fn(Container $c) =>
            $useFakeIntegrations
                ? $c->get(FakePhotoProcessorAdapter::class)
                : $c->get(\App\Adapters\PhotoProcessorAdapter::class)
        );

        // ----------------------------------------------------
        // Dolphin Profile (RobotProfilePort) real + fake
        // ----------------------------------------------------
        $this->set(\App\Adapters\DolphinProfileAdapter::class, function(Container $c) {
            $d = $c->config()['integrations']['dolphin'];
            return new \App\Adapters\DolphinProfileAdapter(
                $c->get(\App\Adapters\HttpClient::class),
                $c->get(\App\Utils\ContractValidator::class),
                $d['base_url'],
                $d['api_key']
            );
        });

        $this->set(FakeRobotProfileAdapter::class, fn() => new FakeRobotProfileAdapter());

        $this->set(\App\Adapters\Ports\RobotProfilePort::class, fn(Container $c) =>
            $useFakeIntegrations
                ? $c->get(FakeRobotProfileAdapter::class)
                : $c->get(\App\Adapters\DolphinProfileAdapter::class)
        );

        // ----------------------------------------------------
        // Robot API (RobotPort) real + fake
        // ----------------------------------------------------
        $this->set(\App\Adapters\RobotApiAdapter::class, function(Container $c) {
            $r = $c->config()['integrations']['robot'];
            return new \App\Adapters\RobotApiAdapter(
                $c->get(\App\Adapters\HttpClient::class),
                $c->get(\App\Utils\ContractValidator::class),
                $r['base_url'],
                $r['api_key']
            );
        });

        $this->set(FakeRobotApiAdapter::class, fn() => new FakeRobotApiAdapter());

        $this->set(\App\Adapters\Ports\RobotPort::class, fn(Container $c) =>
            $useFakeIntegrations
                ? $c->get(FakeRobotApiAdapter::class)
                : $c->get(\App\Adapters\RobotApiAdapter::class)
        );

        // ----------------------------------------------------
        // Marketplace (Avito) real + fake
        // ----------------------------------------------------
        $this->set(\App\Adapters\AvitoMarketplaceAdapter::class, fn() => new \App\Adapters\AvitoMarketplaceAdapter());
        $this->set(FakeMarketplaceAdapter::class, fn() => new FakeMarketplaceAdapter());

        $this->set(\App\Adapters\Ports\MarketplacePort::class, fn(Container $c) =>
            $useFakeIntegrations
                ? $c->get(FakeMarketplaceAdapter::class)
                : $c->get(\App\Adapters\AvitoMarketplaceAdapter::class)
        );

        // ----------------------------------------------------
        // WS
        // ----------------------------------------------------
        $this->set(\App\WS\WsServerInterface::class, fn() => new \App\WS\WsServer());
        $this->set(\App\WS\WsEmitter::class,
            fn(Container $c) => new \App\WS\WsEmitter($c->get(\App\WS\WsServerInterface::class))
        );

        // ----------------------------------------------------
        // HealthAdapter — через порты
        // ----------------------------------------------------
        $this->set(\App\Adapters\HealthAdapter::class, fn(Container $c) => new \App\Adapters\HealthAdapter(
            $c->get(\App\Adapters\Ports\ParserPort::class),
            $c->get(\App\Adapters\Ports\PhotoProcessorPort::class),
            $c->get(\App\Adapters\Ports\StoragePort::class),
            $c->get(\App\Adapters\Ports\RobotPort::class),
            $c->get(\App\Adapters\Ports\RobotProfilePort::class),
        ));

        // ----------------------------------------------------
        // Modules Services
        // ----------------------------------------------------
        $this->set(\Backend\Modules\Auth\AuthService::class,
            fn(Container $c) => new \Backend\Modules\Auth\AuthService(
                $c->get(\PDO::class),
                $c->config()['auth']
            )
        );

        $this->set(\Backend\Modules\Users\UsersService::class,
            fn(Container $c) => new \Backend\Modules\Users\UsersService($c->get(\PDO::class))
        );

        $this->set(\Backend\Modules\Cards\CardsService::class,
            fn(Container $c) => new \Backend\Modules\Cards\CardsService(
                $c->get(\PDO::class),
                $c->get(\App\Queues\QueueService::class)
            )
        );

        $this->set(\Backend\Modules\Parser\ParserService::class,
            fn(Container $c) => new \Backend\Modules\Parser\ParserService($c->get(\PDO::class))
        );

        $this->set(\Backend\Modules\Photos\PhotosService::class,
            fn(Container $c) => new \Backend\Modules\Photos\PhotosService($c->get(\PDO::class))
        );

        $this->set(\Backend\Modules\Export\ExportService::class,
            fn(Container $c) => new \Backend\Modules\Export\ExportService($c->get(\PDO::class))
        );

        $this->set(\Backend\Modules\Publish\PublishService::class,
            fn(Container $c) => new \Backend\Modules\Publish\PublishService($c->get(\PDO::class))
        );

        $this->set(\Backend\Modules\Admin\AdminService::class,
            fn(Container $c) => new \Backend\Modules\Admin\AdminService(
                $c->get(\App\Queues\QueueRepository::class),
                $c->get(\App\Queues\QueueService::class),
                $c->get(\App\Queues\DlqRepository::class),
                $c->get(\App\Adapters\HealthAdapter::class),
                $c->get(\App\WS\WsEmitter::class),
                $c->get(\PDO::class),
            )
        );

        // ----------------------------------------------------
        // Controllers
        // ----------------------------------------------------
        $this->set(\Backend\Modules\Auth\AuthController::class,
            fn(Container $c) => new \Backend\Modules\Auth\AuthController(
                $c->get(\Backend\Modules\Auth\AuthService::class)
            )
        );

        $this->set(\Backend\Modules\Users\UsersController::class,
            fn(Container $c) => new \Backend\Modules\Users\UsersController(
                $c->get(\Backend\Modules\Users\UsersService::class)
            )
        );

        $this->set(\Backend\Modules\Cards\CardsController::class,
            fn(Container $c) => new \Backend\Modules\Cards\CardsController(
                $c->get(\Backend\Modules\Cards\CardsService::class)
            )
        );

        $this->set(\Backend\Modules\Parser\ParserController::class,
            fn(Container $c) => new \Backend\Modules\Parser\ParserController(
                $c->get(\Backend\Modules\Parser\ParserService::class),
                $c->get(\App\Queues\QueueService::class)
            )
        );

        $this->set(\Backend\Modules\Photos\PhotosController::class,
            fn(Container $c) => new \Backend\Modules\Photos\PhotosController(
                $c->get(\Backend\Modules\Photos\PhotosService::class)
            )
        );

        $this->set(\Backend\Modules\Export\ExportController::class,
            fn(Container $c) => new \Backend\Modules\Export\ExportController(
                $c->get(\Backend\Modules\Export\ExportService::class)
            )
        );

        $this->set(\Backend\Modules\Publish\PublishController::class,
            fn(Container $c) => new \Backend\Modules\Publish\PublishController(
                $c->get(\Backend\Modules\Publish\PublishService::class)
            )
        );

        $this->set(\Backend\Modules\Admin\AdminController::class,
            fn(Container $c) => new \Backend\Modules\Admin\AdminController(
                $c->get(\Backend\Modules\Admin\AdminService::class)
            )
        );
    }
}
