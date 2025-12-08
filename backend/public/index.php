<?php
// backend/src/Workers/WorkerDaemon.php

namespace Backend\Workers;

use Backend\Server\Container;

final class WorkerDaemon
{
    public function __construct(
        private Container $c,
        private string $type,     // parser|photos|export|publish|robot_status
        private int $sleepMs = 300
    ) {}

    public function run(): void
    {
        $worker = $this->buildWorker($this->type);

        while (true) {
            $worker->tick();
            usleep($this->sleepMs * 1000);
        }
    }

    private function buildWorker(string $type)
    {
        $wid = $this->c->config()['workers']['id'] . ':' . $type;

        return match($type) {
            'parser' => new \App\Workers\ParserWorker(
                $this->c->get(\App\Queues\QueueService::class),
                $wid,
                $this->c->get(\App\Adapters\ParserAdapter::class),
                $this->c->get(\Backend\Modules\Parser\ParserService::class),
                $this->c->get(\Backend\Modules\Cards\CardsService::class),
                $this->c->get(\App\WS\WsEmitter::class),
            ),
            'photos' => new \App\Workers\PhotosWorker(
                $this->c->get(\App\Queues\QueueService::class),
                $wid,
                $this->c->get(\App\Adapters\PhotoApiAdapter::class),
                $this->c->get(\App\Adapters\S3Adapter::class),
                $this->c->get(\Backend\Modules\Photos\PhotosService::class),
                $this->c->get(\Backend\Modules\Export\ExportService::class),
                $this->c->get(\App\WS\WsEmitter::class),
            ),
            'export' => new \App\Workers\ExportWorker(
                $this->c->get(\App\Queues\QueueService::class),
                $wid,
                $this->c->get(\App\Adapters\S3Adapter::class),
                $this->c->get(\Backend\Modules\Export\ExportService::class),
                $this->c->get(\Backend\Modules\Publish\PublishService::class),
                $this->c->get(\App\WS\WsEmitter::class),
            ),
            'publish' => new \App\Workers\PublishWorker(
                $this->c->get(\App\Queues\QueueService::class),
                $wid,
                $this->c->get(\App\Adapters\AvitoAdapter::class),
                $this->c->get(\App\Adapters\RobotAdapter::class),
                $this->c->get(\App\Adapters\DolphinAdapter::class),
                $this->c->get(\Backend\Modules\Publish\PublishService::class),
                $this->c->get(\Backend\Modules\Cards\CardsService::class),
                $this->c->get(\App\WS\WsEmitter::class),
            ),
            'robot_status' => new \App\Workers\RobotStatusWorker(
                $this->c->get(\App\Queues\QueueService::class),
                $wid,
                $this->c->get(\App\Adapters\RobotAdapter::class),
                $this->c->get(\App\Adapters\DolphinAdapter::class),
                $this->c->get(\App\Adapters\AvitoAdapter::class),
                $this->c->get(\Backend\Modules\Publish\PublishService::class),
                $this->c->get(\App\WS\WsEmitter::class),
            ),
            default => throw new \RuntimeException("Unknown worker type: {$type}")
        };
    }
}
