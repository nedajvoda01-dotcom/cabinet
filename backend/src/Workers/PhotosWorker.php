<?php
// backend/src/Workers/PhotosWorker.php

namespace App\Workers;

use App\Adapters\Ports\PhotoProcessorPort;
use App\Adapters\Ports\StoragePort;
use App\Modules\Export\ExportService;
use App\Modules\Photos\PhotosService;
use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\WS\WsEmitter;

final class PhotosWorker extends BaseWorker
{
    private array $lastJobMeta = [];

    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private PhotoProcessorPort $photoApi,
        private StoragePort $s3,
        private PhotosService $photosService,
        private ExportService $exportService,
        private WsEmitter $ws
    ) {
        parent::__construct($queues, $workerId);
    }

    protected function queueType(): string
    {
        return QueueTypes::PHOTOS;
    }

    protected function handle(QueueJob $job): void
    {
        $cardId = $job->entityId;
        $this->lastJobMeta = [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'card_id' => $cardId,
            'task_id' => $job->payload['task_id'] ?? null,
            'stage' => 'photos',
            'idempotency_key' => $this->idempotencyKey($job),
        ];

        $this->emitStage($job, 'running');

        // ожидаем: getRawPhotos(cardId) -> [{order, raw_key, raw_url, mask_params?}]
        $rawPhotos = $this->photosService->getRawPhotos($cardId);
        if (!$rawPhotos) {
            // нечего обрабатывать — считаем stage done
            $this->photosService->markStageDone($cardId);
            $this->emitStage($job, 'done', ['progress' => 100]);
            return;
        }

        $total = count($rawPhotos);
        $done = 0;
        $masked = [];

        foreach ($rawPhotos as $p) {
            $rawUrl = $p['raw_url'] ?? null;
            if (!$rawUrl) continue;

            $maskParams = (array)($p['mask_params'] ?? []);
            $order = (int)($p['order'] ?? ($done + 1));

            $res = $this->photoApi->maskPhoto(
                $rawUrl,
                $maskParams,
                $this->idempotencyKey($job, 'mask_' . $order)
            );

            // download masked from Photo API and upload to s3 masked/
            $bin = $this->downloadBinary($res['masked_url']);
            $ext = $this->guessExt($res['masked_url']) ?? 'jpg';
            $key = "masked/{$cardId}/{$order}.{$ext}";
            $this->s3->putObject($key, $bin, "image/{$ext}");

            $masked[] = [
                'order' => $order,
                'masked_key' => $key,
                'masked_url' => $this->s3->publicUrl($key),
                'meta' => $res['meta'] ?? [],
            ];

            $done++;
            $this->emitStage($job, 'running', [
                'progress' => $total ? (int)(($done / $total) * 100) : 100,
                'done' => $done,
                'total' => $total,
            ]);
        }

        // сохраняем masked в модуль Photos
        $this->photosService->attachMaskedPhotos($cardId, $masked);
        $this->photosService->markStageDone($cardId);

        // создаём экспортный пакет и ставим export job
        $exportId = $this->exportService->createExport($cardId);
        $this->queues->enqueueExport($exportId, [
            'card_id' => $cardId,
            'correlation_id' => $this->lastJobMeta['correlation_id'],
            'idempotency_key' => $this->idempotencyKey($job, 'export'),
        ]);

        $this->emitStage($job, 'done', ['progress' => 100]);
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
            'card_id' => $this->lastJobMeta['card_id'] ?? $job->entityId,
            'task_id' => $this->lastJobMeta['task_id'] ?? ($job->payload['task_id'] ?? null),
            'stage' => 'photos',
            'status' => $status,
            'idempotency_key' => $this->lastJobMeta['idempotency_key'] ?? $this->idempotencyKey($job),
        ], $extra);

        $this->ws->emit('pipeline.stage', $payload);
    }

    private function downloadBinary(string $url): string
    {
        $bin = @file_get_contents($url);
        if ($bin === false) {
            throw new \App\Adapters\AdapterException(
                "Masked download failed: {$url}",
                "photos_masked_download",
                true,
                ['url' => $url]
            );
        }
        return $bin;
    }

    private function guessExt(string $url): ?string
    {
        $p = parse_url($url, PHP_URL_PATH);
        if (!$p) return null;
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        return $ext ?: null;
    }
}
