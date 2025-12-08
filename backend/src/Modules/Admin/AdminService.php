<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

/**
 * AdminService
 *
 * Бизнес-логика Admin-домена.
 * Контроллеры зовут только сервис.
 */
final class AdminService
{
    public function __construct(
        private AdminModel $model,
        private AdminJobs $jobs
    ) {}

    /**
     * Пример публичного метода сервиса.
     * Сюда приходит DTO из Schemas.
     */
    public function exampleAction(array $dto): array
    {
        // Бизнес-логика будет тут.
        // Сейчас просто эхо.
        return [
            'echo' => $dto['example'],
            'options' => $dto['options'] ?? [],
        ];
    }

    /**
     * Пример: получить админа.
     */
    public function getAdmin(int $id): array
    {
        return $this->model->getAdminById($id);
    }

    /**
     * Пример: список админов.
     */
    public function listAdmins(int $limit = 50, int $offset = 0): array
    {
        return $this->model->listAdmins($limit, $offset);
    }
}
