<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO;

use PDO;

final class MigrationsRunner
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        $this->createUsersTable();
        $this->createAccessRequestsTable();
        $this->createTasksTable();
        $this->createPipelineStatesTable();
        $this->createIdempotencyKeysTable();
    }

    private function createUsersTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            role TEXT NOT NULL,
            scopes_json TEXT NOT NULL,
            is_active INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
        SQL;

        $this->pdo->exec($sql);
    }

    private function createAccessRequestsTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS access_requests (
            id TEXT PRIMARY KEY,
            requested_by TEXT NOT NULL,
            status TEXT NOT NULL,
            requested_at TEXT NOT NULL,
            resolved_at TEXT NULL,
            resolved_by TEXT NULL
        )
        SQL;

        $this->pdo->exec($sql);
    }

    private function createTasksTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS tasks (
            id TEXT PRIMARY KEY,
            created_by TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
        SQL;

        $this->pdo->exec($sql);
    }

    private function createPipelineStatesTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS pipeline_states (
            task_id TEXT PRIMARY KEY,
            stage TEXT NOT NULL,
            status TEXT NOT NULL,
            attempt INTEGER NOT NULL,
            last_error_kind TEXT NULL,
            is_terminal INTEGER NOT NULL,
            updated_at TEXT NOT NULL
        )
        SQL;

        $this->pdo->exec($sql);
    }

    private function createIdempotencyKeysTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS idempotency_keys (
            actor_id TEXT NOT NULL,
            idem_key TEXT NOT NULL,
            task_id TEXT NOT NULL,
            created_at TEXT NOT NULL,
            PRIMARY KEY (actor_id, idem_key)
        )
        SQL;

        $this->pdo->exec($sql);
    }
}
