<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

/**
 * AdminJobs
 *
 * Фоновые задания Admin-домена.
 * Сейчас пустой контейнер для будущих задач.
 */
final class AdminJobs
{
    public function __construct(
        // сюда обычно инжектится очередь/лог/конфиг
    ) {}

    /**
     * Пример постановки job.
     */
    public function dispatchExampleJob(array $payload): void
    {
        // Заглушка. Дальше подключим реальную очередь.
        // $this->queue->push('admin.example', $payload);
    }
}
