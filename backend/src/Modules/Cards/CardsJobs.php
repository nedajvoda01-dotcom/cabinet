<?php
declare(strict_types=1);

namespace Backend\Modules\Cards;

/**
 * CardsJobs
 *
 * Фоновые задачи Cards-домена:
 *  - ручные ретраи карточек
 *  - реиндексация/пересборка при необходимости
 */
final class CardsJobs
{
    public function __construct(
        // TODO: инжект вашего QueueBus / WorkerDispatcher
        // private QueueBus $bus
    ) {}

    public function dispatchCardRetry(int $cardId, string $reason, bool $force): void
    {
        // TODO: заменить на реальную очередь
        // $this->bus->push('cards.retry', [
        //     'card_id' => $cardId,
        //     'reason' => $reason,
        //     'force' => $force,
        // ]);
    }

    public function dispatchReindexCard(int $cardId): void
    {
        // TODO optional
        // $this->bus->push('cards.reindex', ['card_id' => $cardId]);
    }
}
