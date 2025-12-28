<?php
// backend/src/Workers/PublishWorker.php

namespace App\Workers;

use App\Adapters\Ports\MarketplacePort;
use App\Adapters\Ports\RobotPort;
use App\Adapters\Ports\RobotProfilePort;
use App\Modules\Cards\CardsService;
use App\Modules\Publish\PublishService;
use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\WS\WsEmitter;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\Dlq\FileDlqWriter;
use Backend\Application\Pipeline\Idempotency\InMemoryIdempotencyStore;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;
use Backend\Application\Pipeline\Reliability\ReliabilityHandler;
use Backend\Application\Pipeline\Retry\ErrorClassifier;
use Backend\Application\Pipeline\Retry\RetryPolicy;

final class PublishWorker extends BaseWorker
{
    private array $lastJobMeta = [];
    private ?ReliabilityHandler $reliabilityHandler = null;

    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private MarketplacePort $avitoAdapter,
        private RobotPort $robot,
        private RobotProfilePort $dolphin,
        private PublishService $publishService,
        private CardsService $cardsService,
        private WsEmitter $ws,
        private ?JobDispatcher $pipeline = null
    ) {
        parent::__construct($queues, $workerId);
        $this->pipeline = $this->pipeline ?? new JobDispatcher($queues);
    }

    protected function queueType(): string
    {
        return QueueTypes::PUBLISH;
    }

    protected function handle(QueueJob $job): void
    {
        if (isset($job->payload['trace_id'])) {
            TraceContext::setCurrent(TraceContext::fromString((string)$job->payload['trace_id']));
        }

        $cardId = $job->entityId;
        $this->lastJobMeta = [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'card_id' => $cardId,
            'publish_task_id' => $job->payload['task_id'] ?? null,
            'stage' => 'publish',
            'idempotency_key' => $this->idempotencyKey($job),
        ];

        $this->emitStage($job, 'running');

        // 1) собрать snapshot карточки
        $snapshot = $this->cardsService->snapshotForPublish($cardId);

        // 2) Avito payload (pure mapping)
        $avitoPayload = $this->avitoAdapter->mapCard($snapshot);

        // 3) allocate Dolphin profile
        $profile = $this->dolphin->allocateProfile($snapshot, $this->idempotencyKey($job, 'dolphin_allocate'));
        $this->dolphin->startProfile((string)$profile['profile_id'], $this->idempotencyKey($job, 'dolphin_start'));

        // 4) start robot session
        $session = $this->robot->start($profile, $this->idempotencyKey($job, 'robot_start'));

        // 5) publish via robot
        $result = $this->robot->publish(
            (string)$session['session_id'],
            $avitoPayload,
            $this->idempotencyKey($job, 'robot_publish')
        );

        // 6) сохранить publish job в модуле Publish
        $publishJobId = $this->publishService->createJob(
            $cardId,
            (string)$session['session_id'],
            (string)$result['avito_item_id'],
            $result
        );

        // 7) поставить робот-статус чекер
        $this->pipeline->enqueue(Job::create(
            JobType::ROBOT_STATUS,
            'publish_job',
            $publishJobId,
            [
                'avito_item_id' => $result['avito_item_id'],
                'session_id' => $session['session_id'],
                'profile_id' => $profile['profile_id'],
                'correlation_id' => $this->lastJobMeta['correlation_id'],
            ],
            $this->idempotencyKey($job, 'robot_status'),
            TraceContext::ensure()->traceId()
        ));
        $this->lastJobMeta['publish_job_id'] = $publishJobId;

        $this->emitStage($job, 'running', [
            'publish_job_id' => $publishJobId,
            'avito_item_id' => $result['avito_item_id'],
        ]);
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
            'publish_job_id' => $this->lastJobMeta['publish_job_id'] ?? null,
            'stage' => 'publish',
            'status' => $status,
            'idempotency_key' => $this->lastJobMeta['idempotency_key'] ?? $this->idempotencyKey($job),
        ], $extra);

        $this->ws->emit('pipeline.stage', $payload);
    }

    protected function reliability(): ?ReliabilityHandler
    {
        if ($this->reliabilityHandler === null) {
            $this->reliabilityHandler = new ReliabilityHandler(
                new RetryPolicy(new ErrorClassifier()),
                new FileDlqWriter(__DIR__ . '/../../var/dlq.log'),
                new InMemoryIdempotencyStore(),
                $this->queues,
                true,
            );
        }

        return $this->reliabilityHandler;
    }
}
