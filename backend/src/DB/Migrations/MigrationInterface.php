<?php
// backend/src/DB/Migrations/Migrator.php

namespace Backend\DB\Migrations;

use Backend\DB\Db;
use PDO;

final class Migrator
{
    /** @var MigrationInterface[] */
    private array $migrations = [];

    public function __construct(private ?PDO $db = null)
    {
        $this->db = $db ?: Db::pdo();
        $this->ensureSchema();
    }

    public function add(MigrationInterface $m): self
    {
        $this->migrations[$m->id()] = $m;
        return $this;
    }

    public function addMany(array $migrations): self
    {
        foreach ($migrations as $m) $this->add($m);
        return $this;
    }

    public function up(): array
    {
        ksort($this->migrations);

        $applied = $this->appliedIds();
        $ran = [];

        foreach ($this->migrations as $id => $m) {
            if (isset($applied[$id])) continue;

            $this->db->beginTransaction();
            try {
                $m->up($this->db);
                $this->markApplied($id);
                $this->db->commit();
                $ran[] = $id;
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        return $ran;
    }

    public function down(int $steps = 1): array
    {
        krsort($this->migrations);

        $applied = array_keys($this->appliedIds());
        $ran = [];

        foreach ($applied as $id) {
            if (!isset($this->migrations[$id])) continue;
            $m = $this->migrations[$id];

            $this->db->beginTransaction();
            try {
                $m->down($this->db);
                $this->markRolledBack($id);
                $this->db->commit();
                $ran[] = $id;
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }

            if (count($ran) >= $steps) break;
        }

        return $ran;
    }

    // ---------- internals ----------

    private function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id VARCHAR(64) PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT NOW()
            );
        ");
    }

    private function appliedIds(): array
    {
        $rows = $this->db->query("SELECT id FROM schema_migrations")->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) $out[$r['id']] = true;
        return $out;
    }

    private function markApplied(string $id): void
    {
        $st = $this->db->prepare("INSERT INTO schema_migrations (id) VALUES (:id)");
        $st->execute([':id' => $id]);
    }

    private function markRolledBack(string $id): void
    {
        $st = $this->db->prepare("DELETE FROM schema_migrations WHERE id=:id");
        $st->execute([':id' => $id]);
    }
}
