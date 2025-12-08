<?php
// backend/src/DB/Db.php

namespace Backend\DB;

use PDO;
use PDOException;

final class Db
{
    private static ?PDO $pdo = null;

    /**
     * Получить PDO (singleton).
     * Конфиг берём из env:
     *  DB_DSN="pgsql:host=localhost;port=5432;dbname=autocontent"
     *  DB_USER="postgres"
     *  DB_PASS="postgres"
     */
    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;

        $dsn  = getenv('DB_DSN')  ?: 'pgsql:host=localhost;port=5432;dbname=autocontent';
        $user = getenv('DB_USER') ?: 'postgres';
        $pass = getenv('DB_PASS') ?: 'postgres';

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("DB connect failed: ".$e->getMessage(), 0, $e);
        }

        self::$pdo = $pdo;
        return $pdo;
    }

    public static function close(): void
    {
        self::$pdo = null;
    }
}
