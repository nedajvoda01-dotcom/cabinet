<?php
declare(strict_types=1);

namespace Backend\Modules\Photos;

use RuntimeException;
use Backend\Utils\StateMachine;

/**
 * PhotosService
 *
 * Бизнес-логика:
 *  - запуск фото-конвейера
 *  - retry
 *  - webhook done/failed
 *  - синхронизация фото в payload карты
 *  - SM-переходы карточки
 */
final class PhotosService
{
    public function __construct(
        private PhotosModel $model,
        private PhotosJobs $jobs,
        private StateMachine $sm
    ) {}

    public function run(array $dto, ?int $actorUserId): array
    {
        $cardId = $dto['card_id'];
        $force = (bool)$dto['force'];

        $cardStatus = $this->sm->getEntityStatus('card', $cardId);
        if (!$this->sm->can('card', $cardStatus, 'photos', ['force' => $force])) {
            throw new RuntimeException("Photos not allowed from status: {$cardStatus}");
        }

        $task = $this->model->createTask(
            $cardId,
            $dto['mode'],
            $dto['source_urls'] ?? [],
            $dto['params'] ?? []
        );

        // Переведём карточку в photos_processing (через SM)
        $to = $this->sm->nextStatus('card', $cardStatus, 'photos', []);
        if ($to) $this->sm->applyStatus('card', $cardId, $to);

        $this->jobs->dispatchPhotosRun($cardId, (int)$task['id'], $dto['correlation_id'] ?? null);
        $this->model->writeAudit($actorUserId, 'photos_run', "Photo task #{$task['id']} for card #{$cardId} queued");

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

    public function retryTask(int $taskId, array $dto, ?int $actorUserId): array
    {
        $task = $this->model->getTaskById($taskId);

        if ($task['status'] === 'running') {
            throw new RuntimeException("Task is running");
        }
        if ($task['status'] === 'done' && empty($dto['force'])) {
            throw new RuntimeException("Task already done (use force)");
        }

        $this->model->incrementAttempts($taskId);
        $updated = $this->model->updateTaskStatus($taskId, 'queued', null, null);
        $this->jobs->dispatchPhotosRetry((int)$task['card_id'], $taskId, $dto['reason'], (bool)$dto['force'], $dto['correlation_id'] ?? null);
        $this->model->writeAudit($actorUserId, 'photos_retry', "Photo task #{$taskId} retry requested ({$dto['reason']})");

        return $updated;
    }

    public function listCardPhotos(int $cardId): array
    {
        return $this->model->listCardPhotos($cardId);
    }

    public function deletePhoto(int $photoId, ?int $actorUserId): void
    {
        $this->model->deletePhoto($photoId);
        $this->model->writeAudit($actorUserId, 'photo_delete', "Photo #{$photoId} deleted");
    }

    public function setPrimary(int $cardId, int $photoId, ?int $actorUserId): array
    {
        $this->model->setPrimaryPhoto($cardId, $photoId);
        $this->model->attachPhotosToCardPayload($cardId);
        $this->model->writeAudit($actorUserId, 'photo_primary', "Photo #{$photoId} set primary for card #{$cardId}");
        return $this->model->listCardPhotos($cardId);
    }

    /**
     * Webhook от внешнего фото-сервиса.
     *  - фиксируем статус задачи
     *  - на done: добавляем photos артефакты, обновляем payload.cards.photos
     *  - переводим карточку дальше по SM (action=photos_done или photos_failed)
     */
    public function webhook(array $dto): array
    {
        $taskId = $dto['task_id'];
        $cardId = $dto['card_id'];

        $task = $this->model->getTaskById($taskId);
        if ((int)$task['card_id'] !== $cardId) {
            throw new RuntimeException("Task-card mismatch");
        }

        if ($dto['status'] === 'failed') {
            $updatedTask = $this->model->updateTaskStatus(
                $taskId,
                'failed',
                $dto['error_code'],
                $dto['error_message']
            );

            $curr = $this->sm->getEntityStatus('card', $cardId);
            $to = $this->sm->nextStatus('card', $curr, 'photos_failed', []);
            if ($to) $this->sm->applyStatus('card', $cardId, $to);

            return $updatedTask;
        }

        // done
        $photos = $dto['photos'] ?? [];
        if ($photos) {
            $this->model->addPhotos($cardId, $photos);
            $this->model->attachPhotosToCardPayload($cardId);
        }

        $updatedTask = $this->model->updateTaskStatus($taskId, 'done', null, null);

        $curr = $this->sm->getEntityStatus('card', $cardId);
        $to = $this->sm->nextStatus('card', $curr, 'photos_done', ['photos' => $photos]);
        if ($to) $this->sm->applyStatus('card', $cardId, $to);

        return $updatedTask;
    }
}
