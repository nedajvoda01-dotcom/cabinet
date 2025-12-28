<?php
// backend/src/Workers/RobotStatusWorker.php

namespace App\Workers;

use App\Adapters\Ports\MarketplacePort;
use App\Adapters\Ports\RobotPort;
use App\Adapters\Ports\RobotProfilePort;
use App\Modules\Publish\PublishService;
use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\WS\WsEmitter;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;

final class RobotStatusWorker extends BaseWorker
{
    private array $lastJobMeta = [];

    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private RobotPort $robot,
        private RobotProfilePort $dolphin,
        private MarketplacePort $avitoAdapter,
        private PublishService $publishService,
        private WsEmitter $ws,
        private ?JobDispatcher $pipeline = null
    ) {
        parent::__construct($queues, $workerId);
        $this->pipeline = $this->pipeline ?? new JobDispatcher($queues);
    }

    protected function queueType(): string
    {
        return QueueTypes::ROBOT_STATUS;
    }

    protected function handle(QueueJob $job): void
    {
        if (isset($job->payload['trace_id'])) {
            TraceContext::setCurrent(TraceContext::fromString((string)$job->payload['trace_id']));
        }

        $publishJobId = $job->entityId;
        $this->lastJobMeta = [
            'correlation_id' => $job->payload['correlation_id'] ?? null,
            'publish_job_id' => $publishJobId,
            'stage' => 'robot_status',
            'idempotency_key' => $this->idempotencyKey($job),
        ];

        $this->emitStage($job, 'running');

        $avitoItemId = (string)($job->payload['avito_item_id'] ?? '');
        $sessionId   = (string)($job->payload['session_id'] ?? '');
        $profileId   = (string)($job->payload['profile_id'] ?? '');

        if ($avitoItemId === '') {
            throw new \RuntimeException("RobotStatus payload missing avito_item_id");
        }

        $st = $this->robot->pollStatus($avitoItemId, $this->idempotencyKey($job, 'robot_poll'));
        $normStatus = $this->avitoAdapter->normalizeStatus((string)($st['status'] ?? 'unknown'));

        $this->publishService->updateJobStatus($publishJobId, $normStatus, $st);
        $this->emitStage($job, 'running', [
            'publish_job_id' => $publishJobId,
            'status' => $normStatus,
            'avito_item_id' => $avitoItemId,
            'meta' => $st,
        ]);

        if (in_array($normStatus, ['published', 'publish_failed'], true)) {
            if ($sessionId) {
                $this->robot->stop($sessionId, $this->idempotencyKey($job, 'robot_stop'));
            }
            if ($profileId) {
                $this->dolphin->stopProfile($profileId, $this->idempotencyKey($job, 'dolphin_stop'));
            }

            $this->publishService->markJobFinal($publishJobId);
        } else {
            $this->pipeline->enqueue(Job::create(
                JobType::ROBOT_STATUS,
                'publish_job',
                $publishJobId,
                [
                    'avito_item_id' => $avitoItemId,
                    'session_id' => $sessionId,
                    'profile_id' => $profileId,
                    'correlation_id' => $this->lastJobMeta['correlation_id'],
                ],
                $this->idempotencyKey($job, 'robot_status_retry'),
                TraceContext::ensure()->traceId()
            ));
        }
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
            'publish_job_id' => $this->lastJobMeta['publish_job_id'] ?? $job->entityId,
            'stage' => 'robot_status',
            'status' => $status,
            'idempotency_key' => $this->lastJobMeta['idempotency_key'] ?? $this->idempotencyKey($job),
        ], $extra);

        $this->ws->emit('pipeline.stage', $payload);
    }
}
