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
        $rawPhotos = $this->ingestRawPhotos($norm['photos'], $draftCardId);

        // 3) Сохраняем результат в ParserModule
        // ожидаем: attachRawPhotos(draftCardId, rawPhotos)
        $this->parserService->attachRawPhotos($draftCardId, $rawPhotos);

        // 4) Ставим photos pipeline job
        $this->queues->enqueuePhotos($draftCardId, [
            'source' => 'parser',
        ]);

        // 5) WS событие
        $this->ws->emit("card.status.updated", [
            'card_id' => $draftCardId,
            'stage' => 'parser',
            'status' => 'ready',
        ]);
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
