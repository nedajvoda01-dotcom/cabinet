<?php
// backend/src/DB/Migrations/2025_01_01_000001_create_core_tables.php

namespace Backend\DB\Migrations;

use PDO;

final class CreateCoreTables implements MigrationInterface
{
    public function id(): string { return "2025_01_01_000001"; }

    public function up(PDO $db): void
    {
        // ---- queues ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS queue_jobs (
              id BIGSERIAL PRIMARY KEY,
              type VARCHAR(32) NOT NULL,
              entity VARCHAR(32) NOT NULL,
              entity_id BIGINT NOT NULL,
              payload_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              attempts INT NOT NULL DEFAULT 0,
              next_retry_at TIMESTAMP NULL,
              status VARCHAR(16) NOT NULL DEFAULT 'queued',
              last_error_json JSONB NULL,
              locked_at TIMESTAMP NULL,
              locked_by VARCHAR(64) NULL,
              created_at TIMESTAMP NOT NULL DEFAULT NOW(),
              updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS queue_jobs_type_status_retry_idx ON queue_jobs(type, status, next_retry_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS queue_jobs_entity_idx ON queue_jobs(entity, entity_id);");

        $db->exec("
            CREATE TABLE IF NOT EXISTS dlq_jobs (
              id BIGSERIAL PRIMARY KEY,
              job_id BIGINT NOT NULL,
              type VARCHAR(32) NOT NULL,
              entity VARCHAR(32) NOT NULL,
              entity_id BIGINT NOT NULL,
              payload_json JSONB NOT NULL,
              attempts INT NOT NULL,
              last_error_json JSONB NULL,
              created_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS dlq_jobs_type_idx ON dlq_jobs(type);");
        $db->exec("CREATE INDEX IF NOT EXISTS dlq_jobs_entity_idx ON dlq_jobs(entity, entity_id);");

        // ---- users ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
              id BIGSERIAL PRIMARY KEY,
              email VARCHAR(255) UNIQUE NOT NULL,
              password_hash VARCHAR(255) NOT NULL,
              name VARCHAR(255) NULL,
              is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
              created_at TIMESTAMP NOT NULL DEFAULT NOW(),
              updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
              user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
              role VARCHAR(32) NOT NULL,
              PRIMARY KEY(user_id, role)
            );
        ");

        // ---- cards ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS cards (
              id BIGSERIAL PRIMARY KEY,
              source VARCHAR(32) NOT NULL DEFAULT 'auto_ru',
              source_id VARCHAR(128) NULL,
              status VARCHAR(32) NOT NULL DEFAULT 'draft', -- draft|parser_*|photos_*|export_*|publish_*
              title VARCHAR(255) NULL,
              description TEXT NULL,
              vehicle_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              price_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              location_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              meta_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              created_by BIGINT NULL REFERENCES users(id),
              created_at TIMESTAMP NOT NULL DEFAULT NOW(),
              updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS cards_status_idx ON cards(status);");
        $db->exec("CREATE INDEX IF NOT EXISTS cards_source_idx ON cards(source, source_id);");

        // ---- parser payloads ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS parser_payloads (
              id BIGSERIAL PRIMARY KEY,
              external_id VARCHAR(128) NULL,
              payload_json JSONB NOT NULL,
              status VARCHAR(32) NOT NULL DEFAULT 'received',
              created_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS parser_payloads_status_idx ON parser_payloads(status);");

        // ---- photos ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS photos (
              id BIGSERIAL PRIMARY KEY,
              card_id BIGINT NOT NULL REFERENCES cards(id) ON DELETE CASCADE,
              order_no INT NOT NULL,
              raw_key VARCHAR(512) NULL,
              raw_url TEXT NULL,
              masked_key VARCHAR(512) NULL,
              masked_url TEXT NULL,
              status VARCHAR(32) NOT NULL DEFAULT 'raw', -- raw|masked|failed
              is_primary BOOLEAN NOT NULL DEFAULT FALSE,
              meta_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              created_at TIMESTAMP NOT NULL DEFAULT NOW(),
              updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS photos_card_order_idx ON photos(card_id, order_no);");

        // ---- exports ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS exports (
              id BIGSERIAL PRIMARY KEY,
              card_id BIGINT NOT NULL REFERENCES cards(id) ON DELETE CASCADE,
              status VARCHAR(32) NOT NULL DEFAULT 'queued', -- queued|processing|done|failed
              file_key VARCHAR(512) NULL,
              file_url TEXT NULL,
              meta_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              created_at TIMESTAMP NOT NULL DEFAULT NOW(),
              updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS exports_card_idx ON exports(card_id);");

        // ---- publish jobs ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS publish_jobs (
              id BIGSERIAL PRIMARY KEY,
              card_id BIGINT NOT NULL REFERENCES cards(id) ON DELETE CASCADE,
              status VARCHAR(32) NOT NULL DEFAULT 'queued', -- queued|publish_processing|published|publish_failed
              session_id VARCHAR(128) NULL,
              avito_item_id VARCHAR(128) NULL,
              profile_id VARCHAR(128) NULL,
              meta_json JSONB NOT NULL DEFAULT '{}'::jsonb,
              created_at TIMESTAMP NOT NULL DEFAULT NOW(),
              updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS publish_jobs_card_idx ON publish_jobs(card_id);");
        $db->exec("CREATE INDEX IF NOT EXISTS publish_jobs_status_idx ON publish_jobs(status);");

        // ---- system logs (admin/logs) ----
        $db->exec("
            CREATE TABLE IF NOT EXISTS system_logs (
              id BIGSERIAL PRIMARY KEY,
              level VARCHAR(16) NOT NULL, -- info|warn|error
              type VARCHAR(64) NULL,
              message TEXT NOT NULL,
              context_json JSONB NULL,
              correlation_id VARCHAR(128) NULL,
              card_id BIGINT NULL,
              created_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS system_logs_level_idx ON system_logs(level);");
        $db->exec("CREATE INDEX IF NOT EXISTS system_logs_corr_idx ON system_logs(correlation_id);");
        $db->exec("CREATE INDEX IF NOT EXISTS system_logs_card_idx ON system_logs(card_id);");
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS system_logs;");
        $db->exec("DROP TABLE IF EXISTS publish_jobs;");
        $db->exec("DROP TABLE IF EXISTS exports;");
        $db->exec("DROP TABLE IF EXISTS photos;");
        $db->exec("DROP TABLE IF EXISTS parser_payloads;");
        $db->exec("DROP TABLE IF EXISTS cards;");
        $db->exec("DROP TABLE IF EXISTS user_roles;");
        $db->exec("DROP TABLE IF EXISTS users;");
        $db->exec("DROP TABLE IF EXISTS dlq_jobs;");
        $db->exec("DROP TABLE IF EXISTS queue_jobs;");
    }
}
