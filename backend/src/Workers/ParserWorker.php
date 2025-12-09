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
    private array $lastJobMeta = [];

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

        $taskId = (int)($job->payload['task_id'] ?? 0);
        if ($taskId <= 0) {
            throw new \RuntimeException("Parser job payload missing task_id");
        }

        $this->lastJobMeta = [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'task_id' => $taskId,
            'card_id' => null,
            'stage' => 'parser',
        ];

        $this->emitStage($job, 'running');

        $norm = $this->parserAdapter->normalizePush($push);

        // 1) Создаём/обновляем draft карточку (Modules)
        // ожидаем: createDraftFromAd(ad) -> draftCardId
        $draftCardId = $this->cardsService->createDraftFromAd($norm['ad']);
        $this->lastJobMeta['card_id'] = $draftCardId;

        // 2) Инжестим raw фотки в storage
        $rawPhotos = $this->ingestRawPhotos($norm['photos'], $draftCardId);

        // 3) Сохраняем результат в ParserModule
        // ожидаем: attachRawPhotos(draftCardId, rawPhotos)
        $this->parserService->attachRawPhotos($draftCardId, $rawPhotos);

        // 4) Ставим photos pipeline job
        $this->queues->enqueuePhotos($draftCardId, [
            'source' => 'parser',
            'correlation_id' => $this->lastJobMeta['correlation_id'],
        ]);
    }

    protected function afterSuccess(QueueJob $job): void
    {
        $this->emitStage($job, 'done');
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
            'card_id' => $this->lastJobMeta['card_id'] ?? null,
            'task_id' => $this->lastJobMeta['task_id'] ?? ($job->payload['task_id'] ?? null),
            'stage' => 'parser',
            'status' => $status,
        ], $extra);

        $this->ws->emit('pipeline.stage', $payload);
    }

    /**
     * Оркестрация инжеста raw фоток для parser push.
     * Бизнес-правила остаются здесь, адаптер только выполняет IO.
     */
    private function ingestRawPhotos(array $photoUrls, int $cardDraftId): array
    {
        $out = [];
        $order = 0;

        foreach ($photoUrls as $url) {
            $order++;
            if (!is_string($url) || $url === '') continue;

            $binary = $this->parserAdapter->downloadBinary($url);
            $ext = $this->parserAdapter->guessExt($url) ?? 'jpg';

            $key = "raw/{$cardDraftId}/{$order}.{$ext}";
            $publicUrl = $this->parserAdapter->uploadRaw($key, $binary, $ext);

            $out[] = [
                'order' => $order,
                'raw_key' => $key,
                'raw_url' => $publicUrl,
            ];
        }

        return $out;
    }
}
