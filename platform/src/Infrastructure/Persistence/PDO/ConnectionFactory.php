<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO;

use PDO;
use PDOException;

final class ConnectionFactory
{
    private static ?PDO $connection = null;

    public static function create(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $dbPath = getenv('DB_PATH') ?: '/tmp/cabinet.db';
        $dsn = sprintf('sqlite:%s', $dbPath);

        try {
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Enable foreign keys for SQLite
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            self::$connection = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException(sprintf('Failed to connect to database: %s', $e->getMessage()), 0, $e);
        }
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
