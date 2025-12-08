<?php
// backend/src/Workers/RobotStatusWorker.php

namespace App\Workers;

use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\Adapters\RobotAdapter;
use App\Adapters\DolphinAdapter;
use App\Adapters\AvitoAdapter;
use App\Modules\Publish\PublishService;
use App\WS\WsEmitter;

final class RobotStatusWorker extends BaseWorker
{
    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private RobotAdapter $robot,
        private DolphinAdapter $dolphin,
        private AvitoAdapter $avitoAdapter,
        private PublishService $publishService,
        private WsEmitter $ws
    ) {
        parent::__construct($queues, $workerId);
    }

    protected function queueType(): string
    {
        return QueueTypes::ROBOT_STATUS;
    }

    protected function handle(QueueJob $job): void
    {
        $publishJobId = $job->entityId;

        $avitoItemId = (string)($job->payload['avito_item_id'] ?? '');
        $sessionId   = (string)($job->payload['session_id'] ?? '');
        $profileId   = (string)($job->payload['profile_id'] ?? '');

        if ($avitoItemId === '') {
            throw new \RuntimeException("RobotStatus payload missing avito_item_id");
        }

        $st = $this->robot->pollStatus($avitoItemId);
        $normStatus = $this->avitoAdapter->normalizeStatus((string)($st['status'] ?? 'unknown'));

        // ожидаем: updateJobStatus(publishJobId, normStatus, meta)
        $this->publishService->updateJobStatus($publishJobId, $normStatus, $st);

        $this->ws->emit("publish.status.updated", [
            'publish_job_id' => $publishJobId,
            'status' => $normStatus,
            'avito_item_id' => $avitoItemId,
            'meta' => $st,
        ]);

        // если финальный статус — закрываем сессию и профиль
        if (in_array($normStatus, ['published', 'publish_failed'], true)) {
            if ($sessionId) $this->robot->stop($sessionId);
            if ($profileId) $this->dolphin->stopProfile($profileId);

            $this->publishService->markJobFinal($publishJobId);
        } else {
            // не финал — переочередим себя
            $this->queues->enqueueRobotStatus($publishJobId, [
                'avito_item_id' => $avitoItemId,
                'session_id' => $sessionId,
                'profile_id' => $profileId,
            ]);
        }
    }
}
