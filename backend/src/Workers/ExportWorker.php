<?php
// backend/src/Workers/ExportWorker.php

namespace App\Workers;

use App\Adapters\Ports\StoragePort;
use App\Modules\Export\ExportService;
use App\Modules\Publish\PublishService;
use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\WS\WsEmitter;

final class ExportWorker extends BaseWorker
{
    private array $lastJobMeta = [];

    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private StoragePort $s3,
        private ExportService $exportService,
        private PublishService $publishService,
        private WsEmitter $ws
    ) {
        parent::__construct($queues, $workerId);
    }

    protected function queueType(): string
    {
        return QueueTypes::EXPORT;
    }

    protected function handle(QueueJob $job): void
    {
        $exportId = $job->entityId;
        $this->lastJobMeta = [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'export_id' => $exportId,
            'card_id' => $job->payload['card_id'] ?? null,
            'stage' => 'export',
            'idempotency_key' => $this->idempotencyKey($job),
        ];

        $this->emitStage($job, 'running');

        $pkg = $this->exportService->buildPackage($exportId);

        $fileName = $pkg['file_name'] ?? "export_{$exportId}.zip";
        $key = "exports/{$exportId}/{$fileName}";

        $this->s3->putObject($key, (string)$pkg['binary'], $pkg['mime'] ?? 'application/zip');

        $url = $this->s3->publicUrl($key);
        $this->exportService->markDone($exportId, $url);

        $cardId = (int)($pkg['card_id'] ?? 0);
        if ($cardId > 0) {
            $this->queues->enqueuePublish($cardId, [
                'export_id' => $exportId,
                'export_url' => $url,
                'correlation_id' => $this->lastJobMeta['correlation_id'],
                'idempotency_key' => $this->idempotencyKey($job, 'publish'),
            ]);
        }

        $this->emitStage($job, 'done', ['url' => $url]);
    }

    protected function afterFailure(QueueJob $job, array $error, string $outcome): void
    {
        $status = $outcome === 'retrying' ? 'retrying' : 'dlq';
        $this->emitStage($job, $status, ['error' => $error]);
    }

    private function emitStage(QueueJob $job, string $status, array $extra = []): void
    {
        $payload = array_merge([
            'correlation_id' => $this->lastJobMeta['correlation_id'] ?? ($job->payload['correlation_id'] ?? null),
            'export_id' => $this->lastJobMeta['export_id'] ?? $job->entityId,
            'card_id' => $this->lastJobMeta['card_id'] ?? null,
            'stage' => 'export',
            'status' => $status,
            'idempotency_key' => $this->lastJobMeta['idempotency_key'] ?? $this->idempotencyKey($job),
        ], $extra);

        $this->ws->emit('pipeline.stage', $payload);
    }
}
