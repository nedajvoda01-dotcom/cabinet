<?php
// backend/src/Workers/PhotosWorker.php

namespace App\Workers;

use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\Adapters\PhotoApiAdapter;
use App\Adapters\S3Adapter;
use App\Modules\Photos\PhotosService;
use App\Modules\Export\ExportService;
use App\WS\WsEmitter;

final class PhotosWorker extends BaseWorker
{
    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private PhotoApiAdapter $photoApi,
        private S3Adapter $s3,
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

        // ожидаем: getRawPhotos(cardId) -> [{order, raw_key, raw_url, mask_params?}]
        $rawPhotos = $this->photosService->getRawPhotos($cardId);
        if (!$rawPhotos) {
            // нечего обрабатывать — считаем stage done
            $this->photosService->markStageDone($cardId);
            return;
        }

        $total = count($rawPhotos);
        $done = 0;
        $masked = [];

        foreach ($rawPhotos as $p) {
            $rawUrl = $p['raw_url'] ?? null;
            if (!$rawUrl) continue;

            $maskParams = (array)($p['mask_params'] ?? []);
            $res = $this->photoApi->maskPhoto($rawUrl, $maskParams);

            // download masked from Photo API and upload to s3 masked/
            $bin = $this->downloadBinary($res['masked_url']);
            $ext = $this->guessExt($res['masked_url']) ?? 'jpg';
            $order = (int)($p['order'] ?? (++$done));

            $key = "masked/{$cardId}/{$order}.{$ext}";
            $this->s3->putObject($key, $bin, "image/{$ext}");

            $masked[] = [
                'order' => $order,
                'masked_key' => $key,
                'masked_url' => $this->s3->publicUrl($key),
                'meta' => $res['meta'] ?? [],
            ];

            $done++;
            $this->ws->emit("photos.progress", [
                'card_id' => $cardId,
                'done' => $done,
                'total' => $total,
                'percent' => $total ? (int)(($done / $total) * 100) : 100,
            ]);
        }

        // сохраняем masked в модуль Photos
        // ожидаем: attachMaskedPhotos(cardId, masked[])
        $this->photosService->attachMaskedPhotos($cardId, $masked);
        $this->photosService->markStageDone($cardId);

        // создаём экспортный пакет и ставим export job
        // ожидаем: createExport(cardId) -> exportId
        $exportId = $this->exportService->createExport($cardId);
        $this->queues->enqueueExport($exportId, ['card_id' => $cardId]);

        $this->ws->emit("card.status.updated", [
            'card_id' => $cardId,
            'stage' => 'photos',
            'status' => 'ready',
        ]);
    }

    private function downloadBinary(string $url): string
    {
        // простой бинарный fetch через file_get_contents.
        // сетевые ошибки → retryable (кидаем AdapterException)
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
