<?php
// backend/src/Workers/ParserWorker.php

namespace App\Workers;

use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\Adapters\ParserAdapter;
use App\Modules\Parser\ParserService;
use App\Modules\Cards\CardsService;
use App\WS\WsEmitter;

final class ParserWorker extends BaseWorker
{
    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private ParserAdapter $parserAdapter,
        private ParserService $parserService,
        private CardsService $cardsService,
        private WsEmitter $ws
    ) {
        parent::__construct($queues, $workerId);
    }

    protected function queueType(): string
    {
        return QueueTypes::PARSER;
    }

    protected function handle(QueueJob $job): void
    {
        // payload от Modules: { push: ParserPush }
        $push = $job->payload['push'] ?? null;
        if (!$push || !is_array($push)) {
            throw new \RuntimeException("Parser job payload missing push");
        }

        $norm = $this->parserAdapter->normalizePush($push);

        // 1) Создаём/обновляем draft карточку (Modules)
        // ожидаем: createDraftFromAd(ad) -> draftCardId
        $draftCardId = $this->cardsService->createDraftFromAd($norm['ad']);

        // 2) Инжестим raw фотки в storage
        $rawPhotos = $this->parserAdapter->ingestRawPhotos($norm['photos'], $draftCardId, $this->idempotencyKey($job, 'ingest'));

        // 3) Сохраняем результат в ParserModule
        // ожидаем: attachRawPhotos(draftCardId, rawPhotos)
        $this->parserService->attachRawPhotos($draftCardId, $rawPhotos);

        // 4) Ставим photos pipeline job
        $this->queues->enqueuePhotos($draftCardId, [
            'source' => 'parser',
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'idempotency_key' => $this->idempotencyKey($job, 'photos'),
        ]);

        // 5) WS событие
        $this->ws->emit("card.status.updated", [
            'card_id' => $draftCardId,
            'stage' => 'parser',
            'status' => 'ready',
        ]);
    }

    protected function afterFailure(QueueJob $job, array $error, string $outcome): void
    {
        $status = $outcome === 'retrying' ? 'retrying' : 'dlq';
        $this->ws->emit('pipeline.stage', [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'card_id' => $job->entityId,
            'stage' => 'parser',
            'status' => $status,
            'idempotency_key' => $this->idempotencyKey($job),
            'error' => $error,
        ]);
    }
}
