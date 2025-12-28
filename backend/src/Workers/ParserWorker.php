<?php
// backend/src/Workers/ParserWorker.php

namespace App\Workers;

use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;
use App\Adapters\Ports\ParserPort;
use App\Application\Services\RawPhotosIngestService;
use App\Modules\Parser\ParserService;
use App\Modules\Cards\CardsService;
use App\WS\WsEmitter;

final class ParserWorker extends BaseWorker
{
    private array $lastJobMeta = [];

    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private ParserPort $parserAdapter,
        private RawPhotosIngestService $photosIngestService,
        private ParserService $parserService,
        private CardsService $cardsService,
        private WsEmitter $ws,
        private ?JobDispatcher $pipeline = null
    ) {
        parent::__construct($queues, $workerId);
        $this->pipeline = $this->pipeline ?? new JobDispatcher($queues);
    }

    protected function queueType(): string
    {
        return QueueTypes::PARSER;
    }

    protected function handle(QueueJob $job): void
    {
        if (isset($job->payload['trace_id'])) {
            TraceContext::setCurrent(TraceContext::fromString((string)$job->payload['trace_id']));
        }

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
            'idempotency_key' => $this->idempotencyKey($job),
        ];

        $this->emitStage($job, 'running');

        $norm = $this->parserAdapter->normalizePush($push);

        $draftCardId = $this->cardsService->createDraftFromAd($norm['ad']);
        $this->lastJobMeta['card_id'] = $draftCardId;

        $rawPhotos = $this->photosIngestService->ingest($norm['photos'], $draftCardId);

        $this->parserService->attachRawPhotos($draftCardId, $rawPhotos);

        $this->pipeline->enqueue(Job::create(
            JobType::PHOTOS,
            'card',
            $draftCardId,
            [
                'source' => 'parser',
                'correlation_id' => $this->lastJobMeta['correlation_id'],
            ],
            $this->idempotencyKey($job, 'photos'),
            TraceContext::ensure()->traceId()
        ));
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
            'idempotency_key' => $this->lastJobMeta['idempotency_key'] ?? $this->idempotencyKey($job),
        ], $extra);

        $this->ws->emit('pipeline.stage', $payload);
    }
}
