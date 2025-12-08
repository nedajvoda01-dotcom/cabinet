<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

/**
 * AdminService
 *
 * Бизнес-логика Admin-домена:
 *  - мониторинг очередей
 *  - DLQ retry
 *  - health
 *  - логи
 *  - пользователи/роли
 */
final class AdminService
{
    public function __construct(
        private AdminModel $model,
        private AdminJobs $jobs
    ) {}

    // ---------------- Queues ----------------

    public function listQueues(array $dto): array
    {
        $queues = $this->model->getQueuesOverview();

        if (!empty($dto['with_jobs'])) {
            // легкий "расширенный" режим — добавим последние jobs для каждой очереди
            foreach ($queues as &$q) {
                $type = (string)($q['type'] ?? '');
                $q['jobs'] = $type
                    ? $this->model->getQueueJobs($type, 10, 0, null)
                    : [];
            }
        }

        return $queues;
    }

    public function listQueueJobs(array $dto): array
    {
        return $this->model->getQueueJobs(
            $dto['type'],
            $dto['limit'],
            $dto['offset'],
            $dto['status']
        );
    }

    public function pauseQueue(string $type): void
    {
        $this->model->setQueuePaused($type, true);
    }

    public function resumeQueue(string $type): void
    {
        $this->model->setQueuePaused($type, false);
    }

    // ---------------- DLQ ----------------

    public function listDlq(array $dto): array
    {
        return $this->model->getDlqItems(
            $dto['limit'],
            $dto['offset'],
            $dto['type']
        );
    }

    public function getDlqItem(int $id): array
    {
        return $this->model->getDlqItem($id);
    }

    public function retryDlqItem(int $id): void
    {
        $this->model->markDlqRetryRequested($id);
        $this->jobs->dispatchDlqRetry($id);
    }

    public function bulkRetryDlq(array $dto): int
    {
        // Переводим в retrying в БД и получаем количество
        $count = $this->model->bulkRetryDlq($dto['type'], $dto['limit'] ?? null);

        // Для шины задач полезно знать id’шники — выберем их повторно, если нужно.
        // Сейчас оставляем только count: backend обязанность — обеспечить переезд в retrying.
        return $count;
    }

    // ---------------- Health ----------------

    public function health(): array
    {
        return $this->model->getSystemHealth();
    }

    // ---------------- Logs ----------------

    public function logs(array $dto): array
    {
        return $this->model->getLogs(
            $dto['limit'],
            $dto['offset'],
            $dto['user_id'],
            $dto['card_id'],
            $dto['action'],
            $dto['from_ts'],
            $dto['to_ts']
        );
    }

    // ---------------- Users / Roles ----------------

    public function listUsers(array $dto): array
    {
        return $this->model->getUsers(
            $dto['limit'],
            $dto['offset'],
            $dto['q'],
            $dto['role']
        );
    }

    public function updateUserRoles(int $userId, array $roles): array
    {
        return $this->model->updateUserRoles($userId, $roles);
    }
}
