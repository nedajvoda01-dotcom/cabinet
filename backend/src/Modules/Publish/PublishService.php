<?php
declare(strict_types=1);

namespace Backend\Modules\Publish;

use RuntimeException;
use Backend\Utils\StateMachine;

/**
 * PublishService
 *
 * Бизнес-логика:
 *  - постановка publish-task
 *  - cancel/retry
 *  - webhook результатов
 *  - SM-переходы карточки
 *  - метрики
 */
final class PublishService
{
    public function __construct(
        private PublishModel $model,
        private PublishJobs $jobs,
        private StateMachine $sm
    ) {}

    public function run(array $dto, ?int $actorUserId): array
    {
        $cardId = $dto['card_id'];
        $platform = $dto['platform'];
        $force = (bool)$dto['force'];

        $cardStatus = $this->sm->getEntityStatus('card', $cardId);
        if (!$this->sm->can('card', $cardStatus, 'publish', ['platform' => $platform, 'force' => $force])) {
            throw new RuntimeException("Publish not allowed from status: {$cardStatus}");
        }

        $task = $this->model->createTask(
            $cardId,
            $platform,
            $dto['account_id'],
            $dto['params'] ?? []
        );

        // Переведём карточку в publishing через SM
        $to = $this->sm->nextStatus('card', $cardStatus, 'publish', ['platform' => $platform]);
        if ($to) $this->sm->applyStatus('card', $cardId, $to);

        $this->jobs->dispatchPublishRun($cardId, (int)$task['id'], $dto['correlation_id'] ?? null);
        $this->model->writeAudit($actorUserId, 'publish_run', "Publish task #{$task['id']} queued for card #{$cardId} ({$platform})");

        return $task;
    }

    public function listTasks(array $dto): array
    {
        return $this->model->listTasks($dto);
    }

    public function getTask(int $id): array
    {
        return $this->model->getTaskById($id);
    }

    public function cancelTask(int $taskId, array $dto, ?int $actorUserId): array
    {
        $task = $this->model->getTaskById($taskId);

        if (in_array($task['status'], ['done','failed','blocked','canceled'], true)) {
            throw new RuntimeException("Task already finished");
        }

        $updated = $this->model->updateTaskStatus(
            $taskId,
            'canceled',
            null, null,
            'manual_cancel',
            $dto['reason']
        );

        $this->jobs->dispatchPublishCancel((int)$task['card_id'], $taskId, $dto['reason'], $dto['correlation_id'] ?? null);

        // карточка -> publish_canceled если SM позволяет
        $cardId = (int)$task['card_id'];
        $curr = $this->sm->getEntityStatus('card', $cardId);
        $to = $this->sm->nextStatus('card', $curr, 'publish_canceled', []);
        if ($to) $this->sm->applyStatus('card', $cardId, $to);

        $this->model->writeAudit($actorUserId, 'publish_cancel', "Publish task #{$taskId} canceled ({$dto['reason']})");
        return $updated;
    }

    public function retryTask(int $taskId, array $dto, ?int $actorUserId): array
    {
        $task = $this->model->getTaskById($taskId);

        if ($task['status'] === 'running') throw new RuntimeException("Task is running");
        if ($task['status'] === 'done' && empty($dto['force'])) {
            throw new RuntimeException("Task already done (use force)");
        }

        $this->model->incrementAttempts($taskId);
        $updated = $this->model->updateTaskStatus($taskId, 'queued', null, null, null, null);
        $this->jobs->dispatchPublishRetry((int)$task['card_id'], $taskId, $dto['reason'], (bool)$dto['force'], $dto['correlation_id'] ?? null);

        $this->model->writeAudit($actorUserId, 'publish_retry', "Publish task #{$taskId} retry requested ({$dto['reason']})");
        return $updated;
    }

    /**
     * Webhook от внешней площадки/сервиса.
     *  - фиксируем статус publish task
     *  - на done: пишем external_id/url в карточку payload.publish[platform]
     *  - SM-переход карточки (publish_done|publish_failed|publish_blocked)
     */
    public function webhook(array $dto): array
    {
        $taskId = $dto['task_id'];
        $cardId = $dto['card_id'];

        $task = $this->model->getTaskById($taskId);
        if ((int)$task['card_id'] !== $cardId) {
            throw new RuntimeException("Task-card mismatch");
        }

        $platform = (string)$task['platform'];

        if ($dto['status'] === 'failed') {
            $updatedTask = $this->model->updateTaskStatus(
                $taskId,
                'failed',
                null, null,
                $dto['error_code'],
                $dto['error_message']
            );

            $curr = $this->sm->getEntityStatus('card', $cardId);
            $to = $this->sm->nextStatus('card', $curr, 'publish_failed', ['platform' => $platform]);
            if ($to) $this->sm->applyStatus('card', $cardId, $to);

            return $updatedTask;
        }

        if ($dto['status'] === 'blocked') {
            $updatedTask = $this->model->updateTaskStatus(
                $taskId,
                'blocked',
                null, null,
                $dto['error_code'] ?? 'blocked',
                $dto['error_message']
            );

            $curr = $this->sm->getEntityStatus('card', $cardId);
            $to = $this->sm->nextStatus('card', $curr, 'publish_blocked', ['platform' => $platform]);
            if ($to) $this->sm->applyStatus('card', $cardId, $to);

            return $updatedTask;
        }

        // done
        $result = array_filter([
            'external_id' => $dto['external_id'],
            'external_url' => $dto['external_url'],
            'published_at' => time(),
        ], fn($v) => $v !== null);

        if ($result) {
            $this->model->attachPublishResultToCard($cardId, $platform, $result);
        }

        $updatedTask = $this->model->updateTaskStatus(
            $taskId,
            'done',
            $dto['external_id'],
            $dto['external_url'],
            null, null
        );

        $curr = $this->sm->getEntityStatus('card', $cardId);
        $to = $this->sm->nextStatus('card', $curr, 'publish_done', ['platform' => $platform, 'result' => $result]);
        if ($to) $this->sm->applyStatus('card', $cardId, $to);

        return $updatedTask;
    }

    public function metrics(array $dto): array
    {
        return $this->model->getMetrics(
            $dto['from_ts'],
            $dto['to_ts'],
            $dto['platform'],
            $dto['account_id'],
            $dto['bucket_sec']
        );
    }
}
