<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Observability;

use Cabinet\Backend\Application\Observability\AuditEvent;
use Cabinet\Backend\Application\Observability\AuditLogger;
use PDO;

final class SQLiteAuditLogger implements AuditLogger
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function record(AuditEvent $event): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_events (id, ts, actor_id, actor_type, action, target_type, target_id, request_id, data_json, created_at)
             VALUES (:id, :ts, :actor_id, :actor_type, :action, :target_type, :target_id, :request_id, :data_json, :created_at)'
        );

        $stmt->execute([
            'id' => $event->id(),
            'ts' => $event->ts(),
            'actor_id' => $event->actorId(),
            'actor_type' => $event->actorType(),
            'action' => $event->action(),
            'target_type' => $event->targetType(),
            'target_id' => $event->targetId(),
            'request_id' => $event->requestId(),
            'data_json' => json_encode($event->data(), JSON_UNESCAPED_SLASHES),
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
        ]);
    }
}
