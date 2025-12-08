<?php
// backend/src/DB/Transaction.php

namespace Backend\DB;

use PDO;
use Throwable;

final class Transaction
{
    /**
     * Выполнить callback в транзакции.
     * rollback при ошибке.
     *
     * @template T
     * @param callable(PDO):T $fn
     * @return T
     */
    public static function run(callable $fn)
    {
        $db = Db::pdo();
        $db->beginTransaction();
        try {
            $res = $fn($db);
            $db->commit();
            return $res;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
