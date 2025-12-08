<?php
declare(strict_types=1);

namespace Backend\Modules\Auth;

/**
 * AuthJobs
 *
 * Фоновые задачи Auth-домена. По Spec:
 *  - сброс пароля (почта/уведомление)
 *  - чистка истёкших сессий/токенов (можно по крону)
 */
final class AuthJobs
{
    public function __construct(
        // TODO: инжект почтового адаптера / очереди
        // private Mailer $mailer,
        // private QueueBus $bus
    ) {}

    public function dispatchPasswordResetEmail(string $email, string $token): void
    {
        // TODO: реальная отправка через Adapter (например Mailgun/SMTP)
        // $this->mailer->sendReset($email, $token);
    }

    public function dispatchCleanupExpiredSessions(): void
    {
        // TODO: если у вас есть воркер/крон — поставить задачу
        // $this->bus->push('auth.cleanup_sessions', []);
    }
}
