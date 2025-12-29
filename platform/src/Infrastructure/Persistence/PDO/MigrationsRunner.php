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
        $this->createTaskOutputsTable();
        $this->createJobsTable();
        $this->createAuditEventsTable();
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

    private function createTaskOutputsTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS task_outputs (
            task_id TEXT NOT NULL,
            stage TEXT NOT NULL,
            payload_json TEXT NOT NULL,
            created_at TEXT NOT NULL,
            PRIMARY KEY (task_id, stage)
        )
        SQL;

        $this->pdo->exec($sql);
    }

    private function createJobsTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS jobs (
            job_id TEXT PRIMARY KEY,
            task_id TEXT NOT NULL,
            kind TEXT NOT NULL,
            status TEXT NOT NULL,
            attempt INTEGER NOT NULL,
            available_at TEXT NOT NULL,
            last_error_kind TEXT NULL,
            payload_json TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
        SQL;

        $this->pdo->exec($sql);

        // Create index for efficient querying of available jobs
        $indexSql = <<<SQL
        CREATE INDEX IF NOT EXISTS idx_jobs_status_available 
        ON jobs (status, available_at)
        SQL;

        $this->pdo->exec($indexSql);
    }

    private function createAuditEventsTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS audit_events (
            id TEXT PRIMARY KEY,
            ts TEXT NOT NULL,
            actor_id TEXT NULL,
            actor_type TEXT NULL,
            action TEXT NOT NULL,
            target_type TEXT NOT NULL,
            target_id TEXT NOT NULL,
            request_id TEXT NULL,
            data_json TEXT NOT NULL,
            created_at TEXT NOT NULL
        )
        SQL;

        $this->pdo->exec($sql);

        // Create indexes for efficient querying
        $indexes = [
            'CREATE INDEX IF NOT EXISTS idx_audit_events_ts ON audit_events (ts)',
            'CREATE INDEX IF NOT EXISTS idx_audit_events_actor_id ON audit_events (actor_id)',
            'CREATE INDEX IF NOT EXISTS idx_audit_events_action ON audit_events (action)',
        ];

        foreach ($indexes as $indexSql) {
            $this->pdo->exec($indexSql);
        }
    }
}
