<?php
declare(strict_types=1);

namespace Backend\Modules\Parser;

use RuntimeException;
use Backend\Utils\StateMachine;

/**
 * ParserService
 *
 * Бизнес-логика:
 *  - запуск парсинга карточки
 *  - retry
 *  - приём результатов (webhook)
 *  - SM-переходы карточки
 */
final class ParserService
{
    public function __construct(
        private ParserModel $model,
        private ParserJobs $jobs,
        private StateMachine $sm
    ) {}

    /**
     * Создать parser task и поставить в очередь.
     */
    public function run(array $dto, ?int $actorUserId): array
    {
        $cardId = $dto['card_id'];
        $force = (bool)$dto['force'];

        // Проверим, можно ли запускать парсинг из текущего статуса
        $cardStatus = $this->sm->getEntityStatus('card', $cardId);
        if (!$this->sm->can('card', $cardStatus, 'parse', ['force' => $force])) {
            throw new RuntimeException("Parse not allowed from status: {$cardStatus}");
        }

        $task = $this->model->createTask(
            $cardId,
            'card_parse',
            $dto['source_url'],
            $dto['params'] ?? []
        );

        // Переведём карточку в parsing через SM
        $newStatus = $this->sm->nextStatus('card', $cardStatus, 'parse', []);
        if ($newStatus) {
            $this->sm->applyStatus('card', $cardId, $newStatus);
        }

        $this->jobs->dispatchParseRun((int)$task['id']);
        $this->model->writeAudit($actorUserId, 'parser_run', "Parser task #{$task['id']} for card #{$cardId} queued");

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
        $updated = $this->model->updateTaskStatus($taskId, 'queued', null, null, null);

        $this->jobs->dispatchParseRetry($taskId, $dto['reason'], (bool)$dto['force']);
        $this->model->writeAudit($actorUserId, 'parser_retry', "Parser task #{$taskId} retry requested ({$dto['reason']})");

        return $updated;
    }

    /**
     * Вебхук от внешнего парсера.
     * Здесь мы:
     *  - фиксируем статус parser task
     *  - при done: мержим payload в карточку
     *  - переводим карточку дальше по SM (action=parsed)
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
                null,
                $dto['error_code'],
                $dto['error_message']
            );

            // карточку переводим в parse_failed через SM (если разрешено)
            $curr = $this->sm->getEntityStatus('card', $cardId);
            $to = $this->sm->nextStatus('card', $curr, 'parse_failed', []);
            if ($to) $this->sm->applyStatus('card', $cardId, $to);

            return $updatedTask;
        }

        // done
        $parsedPayload = is_array($dto['parsed_payload'] ?? null) ? $dto['parsed_payload'] : [];

        if ($parsedPayload) {
            $this->model->mergeCardPayload($cardId, $parsedPayload);
        }

        $updatedTask = $this->model->updateTaskStatus($taskId, 'done', $parsedPayload);

        // карточку переводим в parsed/next
        $curr = $this->sm->getEntityStatus('card', $cardId);
        $to = $this->sm->nextStatus('card', $curr, 'parsed', ['payload' => $parsedPayload]);
        if ($to) $this->sm->applyStatus('card', $cardId, $to);

        return $updatedTask;
    }
}
