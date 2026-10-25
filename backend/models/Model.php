<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Clase base del patrón Repository/Model.
 * Toda consulta usa consultas preparadas (prevención de inyección SQL).
 */
abstract class Model
{
    protected static function db(): PDO
    {
        return Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    protected static function rows(string $sql, array $params = []): array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected static function row(string $sql, array $params = []): ?array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    protected static function scalar(string $sql, array $params = []): mixed
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false ? null : $value;
    }

    protected static function run(string $sql, array $params = []): int
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Ejecuta un INSERT ... RETURNING id y devuelve el nuevo id. */
    protected static function insert(string $sql, array $params = []): int
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $id = $stmt->fetchColumn();
        return $id === false ? 0 : (int) $id;
    }

    /**
     * Ejecuta un callback dentro de una transacción (atomicidad).
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected static function transaction(callable $callback): mixed
    {
        $pdo = self::db();
        $owns = $pdo->inTransaction();
        if (!$owns) {
            $pdo->beginTransaction();
        }
        try {
            $result = $callback();
            if (!$owns) {
                $pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if (!$owns && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
