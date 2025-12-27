<?php
// backend/src/Workers/ExportWorker.php

namespace App\Workers;

use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\Adapters\S3Adapter;
use App\Modules\Export\ExportService;
use App\Modules\Publish\PublishService;
use App\WS\WsEmitter;

final class ExportWorker extends BaseWorker
{
    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private S3Adapter $s3,
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

        // ожидаем: buildPackage(exportId) -> ['binary'=>string, 'file_name'=>string, 'mime'=>string, 'card_id'=>int]
        $pkg = $this->exportService->buildPackage($exportId);

        $fileName = $pkg['file_name'] ?? "export_{$exportId}.zip";
        $key = "exports/{$exportId}/{$fileName}";

        $this->s3->putObject($key, (string)$pkg['binary'], $pkg['mime'] ?? 'application/zip');

        // ожидаем: markDone(exportId, publicUrl)
        $url = $this->s3->publicUrl($key);
        $this->exportService->markDone($exportId, $url);

        $cardId = (int)($pkg['card_id'] ?? 0);
        if ($cardId > 0) {
            // publish stage
            $this->queues->enqueuePublish($cardId, [
                'export_id' => $exportId,
                'export_url' => $url,
            ]);
        }

        $this->ws->emit("export.progress", [
            'export_id' => $exportId,
            'status' => 'done',
            'url' => $url,
        ]);
    }
}
