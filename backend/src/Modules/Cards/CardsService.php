<?php
declare(strict_types=1);

namespace Backend\Modules\Cards;

use RuntimeException;
use Backend\Utils\StateMachine; // по Spec смена статусов — только через SM

/**
 * CardsService
 *
 * Бизнес-логика Cards:
 *  - CRUD
 *  - поиск/списки
 *  - переходы статусов (StateMachine-first)
 *  - bulk переходы
 *  - manual retry
 */
final class CardsService
{
    public function __construct(
        private CardsModel $model,
        private CardsJobs $jobs,
        private StateMachine $sm
    ) {}

    // -------- CRUD --------

    public function createCard(array $dto, ?int $actorUserId): array
    {
        $card = $this->model->createCard([
            'user_id' => $dto['user_id'] ?? $actorUserId,
            'title' => $dto['title'],
            'description' => $dto['description'],
            'payload' => $dto['payload'] ?? [],
            'status' => $this->sm->initialStatus('card'), // если у SM другой метод — правим здесь
            'is_locked' => false,
        ]);

        $this->model->writeAudit($actorUserId, 'card_create', "Card #{$card['id']} created");
        return $card;
    }

    public function getCard(int $id): array
    {
        return $this->model->getCardById($id);
    }

    public function listCards(array $dto): array
    {
        return $this->model->listCards($dto);
    }

    public function updateCard(int $id, array $dto, ?int $actorUserId): array
    {
        $card = $this->model->getCardById($id);

        // запрет на прямую смену статуса мимо StateMachine (Spec)
        if (isset($dto['status']) && $dto['status'] !== $card['status']) {
            throw new RuntimeException("Status change only via transition");
        }

        $updated = $this->model->updateCard($id, $dto);
        $this->model->writeAudit($actorUserId, 'card_update', "Card #{$id} updated");

        return $updated;
    }

    public function deleteCard(int $id, ?int $actorUserId): void
    {
        $this->model->deleteCard($id);
        $this->model->writeAudit($actorUserId, 'card_delete', "Card #{$id} deleted");
    }

    // -------- Status transitions --------

    public function transitionCard(int $id, array $dto, ?int $actorUserId): array
    {
        $card = $this->model->getCardById($id);

        $from = (string)$card['status'];
        $action = $dto['action'];
        $meta = $dto['meta'] ?? [];

        $to = $this->sm->nextStatus('card', $from, $action, $meta);
        if (!$to) {
            throw new RuntimeException("Transition not allowed: {$from} --{$action}--> ?");
        }

        $updated = $this->model->updateCardStatus($id, $to);

        $this->model->addCardEvent($id, $from, $to, $action, $meta, $actorUserId);
        $this->model->writeAudit($actorUserId, 'card_transition', "Card #{$id}: {$from} -> {$to} ({$action})");

        return $updated;
    }

    public function bulkTransition(array $dto, ?int $actorUserId): array
    {
        $action = $dto['action'];
        $meta = $dto['meta'] ?? [];
        $ids = $dto['card_ids'];

        $updated = [];
        $failed = [];

        foreach ($ids as $id) {
            try {
                $updated[] = $this->transitionCard($id, ['action' => $action, 'meta' => $meta], $actorUserId);
            } catch (\Throwable $e) {
                $failed[] = ['id' => $id, 'error' => $e->getMessage()];
            }
        }

        return [
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    // -------- Retry --------

    public function retryCard(int $id, array $dto, ?int $actorUserId): array
    {
        $card = $this->model->getCardById($id);

        // Retry допускаем только из SM-статусов, где это разрешено
        if (!$this->sm->canRetry('card', (string)$card['status'], $dto['force'] ?? false)) {
            throw new RuntimeException("Retry not allowed for status: {$card['status']}");
        }

        $this->jobs->dispatchCardRetry($id, $dto['reason'], (bool)$dto['force']);
        $this->model->writeAudit($actorUserId, 'card_retry', "Card #{$id} retry requested ({$dto['reason']})");

        return ['queued' => true];
    }
}
