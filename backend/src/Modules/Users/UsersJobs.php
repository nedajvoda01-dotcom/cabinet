<?php
declare(strict_types=1);

namespace Backend\Modules\Users;

/**
 * UsersJobs
 *
 * В MVP фоновых задач Users не требуется по Spec.
 * Файл оставляем как точку расширения:
 * - приглашения
 * - уведомление о ролях/блокировке
 */
final class UsersJobs
{
    public function __construct(
        // TODO: Mailer / QueueBus if needed
    ) {}

    public function dispatchUserCreated(int $userId): void
    {
        // TODO optional: send invite/reset-password email
    }

    public function dispatchUserBlocked(int $userId, string $reason = 'blocked'): void
    {
        // TODO optional: notify user
    }
}
