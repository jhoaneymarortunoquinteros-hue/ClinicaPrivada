<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Conexión única (singleton) a PostgreSQL mediante PDO.
 * Todo acceso a la base de datos pasa por Database::pdo().
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = self::config();
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $cfg['db']['host'],
            (int) $cfg['db']['port'],
            $cfg['db']['name']
        );

        try {
            self::$pdo = new PDO($dsn, (string) $cfg['db']['user'], (string) $cfg['db']['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            self::$pdo->exec("SET client_encoding TO 'UTF8'");
        } catch (PDOException $e) {
            http_response_code(500);
            $msg = !empty($cfg['app']['debug'])
                ? $e->getMessage()
                : 'error de conexión con PostgreSQL';
            exit('No fue posible conectar con la base de datos: ' . $msg);
        }

        return self::$pdo;
    }

    private static function config(): array
    {
        if (!isset($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = require __DIR__ . '/config.php';
        }

        return $GLOBALS['CONFIG'];
    }
}
