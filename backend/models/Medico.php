<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Médicos (perfil vinculado a un usuario con rol "medico").
 */
final class Medico extends Model
{
    private static function filters(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(lower(dv.full_name || ' ' || coalesce(dv.email, '') || ' ' ||
                                dv.license_number) LIKE :q)";
            $params[':q'] = '%' . mb_strtolower($q, 'UTF-8') . '%';
        }

        $specialty = (int) ($filters['specialty_id'] ?? 0);
        if ($specialty > 0) {
            $where[] = 'dv.specialty_id = :specialty';
            $params[':specialty'] = $specialty;
        }

        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = 'dv.active = :active';
            $params[':active'] = (int) $filters['active'] === 1;
        }

        return [implode(' AND ', $where), $params];
    }

    public static function list(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $params] = self::filters($filters);
        $sql = "SELECT * FROM v_doctors dv
                 WHERE {$where}
                 ORDER BY dv.active DESC, dv.full_name";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }
        return self::rows($sql, $params);
    }

    public static function count(array $filters = []): int
    {
        [$where, $params] = self::filters($filters);
        return (int) self::scalar(
            "SELECT COUNT(*) FROM v_doctors dv WHERE {$where}",
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return self::row('SELECT * FROM v_doctors WHERE id = :id', [':id' => $id]);
    }

    public static function findByUserId(int $userId): ?array
    {
        return self::row('SELECT * FROM v_doctors WHERE user_id = :user_id', [':user_id' => $userId]);
    }

    /** Opciones para selects: activos por defecto. */
    public static function selectOptions(bool $onlyActive = true): array
    {
        $sql = 'SELECT id, full_name, email, specialty_name, duration_minutes, active
                  FROM v_doctors';
        if ($onlyActive) {
            $sql .= ' WHERE active = TRUE AND user_active = TRUE';
        }
        return self::rows($sql . ' ORDER BY full_name');
    }

    public static function licenseExists(string $license, int $excludeId = 0): bool
    {
        return (bool) self::scalar(
            'SELECT COUNT(*) FROM doctors WHERE lower(license_number) = lower(:license) AND id <> :id',
            [':license' => $license, ':id' => $excludeId]
        );
    }

    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO doctors (user_id, specialty_id, license_number, professional_title, bio, active)
             VALUES (:user_id, :specialty_id, :license_number, :professional_title, :bio, :active)
             RETURNING id',
            [
                ':user_id'            => (int) $data['user_id'],
                ':specialty_id'       => (int) $data['specialty_id'],
                ':license_number'     => trim((string) $data['license_number']),
                ':professional_title' => $data['professional_title'] ?? null,
                ':bio'                => $data['bio'] ?? null,
                ':active'             => (bool) ($data['active'] ?? true),
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return self::run(
            'UPDATE doctors
                SET specialty_id = :specialty_id, license_number = :license_number,
                    professional_title = :professional_title, bio = :bio, active = :active
              WHERE id = :id',
            [
                ':id'                 => $id,
                ':specialty_id'       => (int) $data['specialty_id'],
                ':license_number'     => trim((string) $data['license_number']),
                ':professional_title' => $data['professional_title'] ?? null,
                ':bio'                => $data['bio'] ?? null,
                ':active'             => (bool) ($data['active'] ?? true),
            ]
        ) > 0;
    }

    /** Activar/desactivar médico (afecta también a su usuario). */
    public static function toggle(int $id): bool
    {
        return self::transaction(static function () use ($id): bool {
            self::run('UPDATE doctors SET active = NOT active WHERE id = :id', [':id' => $id]);
            self::run(
                'UPDATE users SET active = (SELECT active FROM doctors WHERE id = :did) WHERE id = (SELECT user_id FROM doctors WHERE id = :did2)',
                [':did' => $id, ':did2' => $id]
            );
            return true;
        });
    }

    /** Relación médico-usuario para el perfil del médico en sesión. */
    public static function forUser(int $userId): ?array
    {
        return self::findByUserId($userId);
    }
}
