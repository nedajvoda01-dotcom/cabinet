<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO;

use Cabinet\Backend\Application\Ports\UnitOfWork;
use PDO;

final class PDOUnitOfWork implements UnitOfWork
{
    private PDO $pdo;
    private bool $inTransaction = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function begin(): void
    {
        if (!$this->inTransaction) {
            $this->pdo->beginTransaction();
            $this->inTransaction = true;
        }
    }

    public function commit(): void
    {
        if ($this->inTransaction) {
            $this->pdo->commit();
            $this->inTransaction = false;
        }
    }

    public function rollback(): void
    {
        if ($this->inTransaction) {
            $this->pdo->rollBack();
            $this->inTransaction = false;
        }
    }
}
