<?php
// backend/src/Workers/PublishWorker.php

namespace App\Workers;

use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\Adapters\AvitoAdapter;
use App\Adapters\RobotAdapter;
use App\Adapters\DolphinAdapter;
use App\Modules\Publish\PublishService;
use App\Modules\Cards\CardsService;
use App\WS\WsEmitter;

final class PublishWorker extends BaseWorker
{
    public function __construct(
        \App\Queues\QueueService $queues,
        string $workerId,
        private AvitoAdapter $avitoAdapter,
        private RobotAdapter $robot,
        private DolphinAdapter $dolphin,
        private PublishService $publishService,
        private CardsService $cardsService,
        private WsEmitter $ws
    ) {
        parent::__construct($queues, $workerId);
    }

    protected function queueType(): string
    {
        return QueueTypes::PUBLISH;
    }

    protected function handle(QueueJob $job): void
    {
        $cardId = $job->entityId;

        // 1) собрать snapshot карточки
        // ожидаем: snapshotForPublish(cardId) -> array
        $snapshot = $this->cardsService->snapshotForPublish($cardId);

        // 2) Avito payload (pure mapping)
        $avitoPayload = $this->avitoAdapter->mapCard($snapshot);

        // 3) allocate Dolphin profile
        $profile = $this->dolphin->allocateProfile($snapshot);
        $this->dolphin->startProfile((string)$profile['profile_id']);

        // 4) start robot session
        $session = $this->robot->start($profile);

        // 5) publish via robot
        $result = $this->robot->publish((string)$session['session_id'], $avitoPayload);

        // 6) сохранить publish job в модуле Publish
        // ожидаем: createJob(cardId, sessionId, avitoItemId, meta) -> publishJobId
        $publishJobId = $this->publishService->createJob(
            $cardId,
            (string)$session['session_id'],
            (string)$result['avito_item_id'],
            $result
        );

        // 7) поставить робот-статус чекер
        $this->queues->enqueueRobotStatus($publishJobId, [
            'avito_item_id' => $result['avito_item_id'],
            'session_id' => $session['session_id'],
            'profile_id' => $profile['profile_id'],
        ]);

        $this->ws->emit("publish.progress", [
            'card_id' => $cardId,
            'publish_job_id' => $publishJobId,
            'status' => 'publish_processing',
            'avito_item_id' => $result['avito_item_id'],
        ]);
    }
}
