<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Especialidades médicas: definen la duración de cada consulta.
 */
final class Especialidad extends Model
{
    public static function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM specialties';
        if ($onlyActive) {
            $sql .= ' WHERE active = TRUE';
        }
        return self::rows($sql . ' ORDER BY name');
    }

    public static function find(int $id): ?array
    {
        return self::row('SELECT * FROM specialties WHERE id = :id', [':id' => $id]);
    }

    public static function nameExists(string $name, int $excludeId = 0): bool
    {
        return (bool) self::scalar(
            'SELECT COUNT(*) FROM specialties WHERE lower(name) = lower(:name) AND id <> :id',
            [':name' => $name, ':id' => $excludeId]
        );
    }

    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO specialties (name, duration_minutes, description, active)
             VALUES (:name, :duration_minutes, :description, :active) RETURNING id',
            [
                ':name'             => trim((string) $data['name']),
                ':duration_minutes' => (int) $data['duration_minutes'],
                ':description'      => $data['description'] ?? null,
                ':active'           => (bool) ($data['active'] ?? true),
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return self::run(
            'UPDATE specialties
                SET name = :name, duration_minutes = :duration_minutes,
                    description = :description, active = :active
              WHERE id = :id',
            [
                ':id'               => $id,
                ':name'             => trim((string) $data['name']),
                ':duration_minutes' => (int) $data['duration_minutes'],
                ':description'      => $data['description'] ?? null,
                ':active'           => (bool) ($data['active'] ?? true),
            ]
        ) > 0;
    }

    public static function destroy(int $id): bool
    {
        // Protegido por FK ON DELETE RESTRICT: no borra si tiene médicos asignados.
        return self::run('DELETE FROM specialties WHERE id = :id', [':id' => $id]) > 0;
    }

    public static function durationOf(int $id): int
    {
        return (int) (self::scalar('SELECT duration_minutes FROM specialties WHERE id = :id', [':id' => $id]) ?? 15);
    }
}
