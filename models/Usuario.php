<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Usuarios del sistema (base de autenticación de los 4 roles).
 */
final class Usuario extends Model
{
    private static function filters(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // COALESCE: el correo puede ser NULL en cuentas auto-registradas.
            $where[] = '(lower(u.first_name || \' \' || u.last_name || \' \' ||
                          coalesce(u.username, \'\') || \' \' || coalesce(u.email, \'\')) LIKE :q)';
            $params[':q'] = '%' . mb_strtolower($q, 'UTF-8') . '%';
        }

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '') {
            $where[] = 'r.code = :role';
            $params[':role'] = $role;
        }

        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = 'u.active = :active';
            $params[':active'] = (int) $filters['active'] === 1;
        }

        return [implode(' AND ', $where), $params];
    }

    public static function list(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $params] = self::filters($filters);
        $sql = "SELECT u.*, r.code AS role_code, r.name AS role_name
                  FROM users u
                  JOIN roles r ON r.id = u.role_id
                 WHERE {$where}
                 ORDER BY u.active DESC, u.last_name, u.first_name";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }
        return self::rows($sql, $params);
    }

    public static function count(array $filters = []): int
    {
        [$where, $params] = self::filters($filters);
        return (int) self::scalar(
            "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE {$where}",
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return self::row(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = :id',
            [':id' => $id]
        );
    }

    public static function findByEmail(string $email): ?array
    {
        return self::row(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE lower(u.email) = lower(:email)',
            [':email' => $email]
        );
    }

    public static function findByUsername(string $username): ?array
    {
        return self::row(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE lower(u.username) = lower(:username)',
            [':username' => $username]
        );
    }

    public static function roles(): array
    {
        return self::rows('SELECT * FROM roles ORDER BY id');
    }

    public static function roleId(string $code): int
    {
        return (int) self::scalar('SELECT id FROM roles WHERE code = :code', [':code' => $code]);
    }

    public static function byRole(string $code): array
    {
        return self::rows(
            "SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id
              WHERE r.code = :code ORDER BY u.last_name",
            [':code' => $code]
        );
    }

    public static function emailExists(string $email, int $excludeId = 0): bool
    {
        return (bool) self::scalar(
            'SELECT COUNT(*) FROM users WHERE lower(email) = lower(:email) AND id <> :id',
            [':email' => $email, ':id' => $excludeId]
        );
    }

    public static function usernameExists(string $username, int $excludeId = 0): bool
    {
        return (bool) self::scalar(
            'SELECT COUNT(*) FROM users
              WHERE username IS NOT NULL AND lower(username) = lower(:username) AND id <> :id',
            [':username' => $username, ':id' => $excludeId]
        );
    }

    /**
     * Genera un nombre de usuario único a partir de un texto (normalmente el correo).
     * Lo usan los formularios del personal, que no piden nombre de usuario.
     */
    public static function generateUsername(string $seed, int $excludeId = 0): string
    {
        $base = username_slug($seed);
        if ($base === '') {
            $base = 'usuario';
        }
        $base = mb_substr($base, 0, 40, 'UTF-8');

        $candidate = $base;
        $suffix = 2;
        while (self::usernameExists($candidate, $excludeId)) {
            $candidate = mb_substr($base, 0, 37, 'UTF-8') . '-' . $suffix++;
        }
        return $candidate;
    }

    public static function create(array $data): int
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')), 'UTF-8');
        $username = trim((string) ($data['username'] ?? ''));

        // Las cuentas creadas por el personal no piden nombre de usuario:
        // se genera a partir del correo (o del nombre) para poder iniciar sesión.
        if ($username === '') {
            $username = self::generateUsername(
                $email !== '' ? $email : trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''))
            );
        }

        return self::insert(
            'INSERT INTO users (role_id, username, email, password_hash, first_name, last_name, phone, active)
             VALUES (:role_id, :username, :email, :password_hash, :first_name, :last_name, :phone, :active)
             RETURNING id',
            [
                ':role_id'       => (int) $data['role_id'],
                ':username'      => $username,
                ':email'         => $email !== '' ? $email : null,
                ':password_hash' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
                ':first_name'    => trim((string) $data['first_name']),
                ':last_name'     => trim((string) $data['last_name']),
                ':phone'         => $data['phone'] ?? null,
                ':active'        => (bool) ($data['active'] ?? true),
            ]
        );
    }

    public static function update(int $id, array $data, ?string $password = null): bool
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')), 'UTF-8');
        $username = trim((string) ($data['username'] ?? ''));

        $params = [
            ':id'         => $id,
            ':role_id'    => (int) $data['role_id'],
            ':email'      => $email !== '' ? $email : null,
            ':first_name' => trim((string) $data['first_name']),
            ':last_name'  => trim((string) $data['last_name']),
            ':phone'      => $data['phone'] ?? null,
            ':active'     => (bool) ($data['active'] ?? true),
        ];

        $sql = 'UPDATE users SET role_id = :role_id, email = :email,
                       first_name = :first_name, last_name = :last_name,
                       phone = :phone, active = :active';
        // Solo se reescribe el nombre de usuario si viene explícito en los datos.
        if ($username !== '') {
            $sql .= ', username = :username';
            $params[':username'] = $username;
        }
        if ($password !== null && $password !== '') {
            $sql .= ', password_hash = :password_hash';
            $params[':password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE id = :id';

        return self::run($sql, $params) > 0;
    }

    public static function setPassword(int $id, string $plain): void
    {
        self::run(
            'UPDATE users SET password_hash = :hash WHERE id = :id',
            [':hash' => password_hash($plain, PASSWORD_BCRYPT), ':id' => $id]
        );
    }

    /** Activa/desactiva la cuenta (también el perfil asociado si existe). */
    public static function toggle(int $id): bool
    {
        return self::transaction(static function () use ($id): bool {
            self::run('UPDATE users SET active = NOT active WHERE id = :id', [':id' => $id]);
            self::run(
                'UPDATE doctors SET active = (SELECT active FROM users WHERE id = :uid) WHERE user_id = :uid2',
                [':uid' => $id, ':uid2' => $id]
            );
            return true;
        });
    }

    public static function touchLogin(int $id): void
    {
        self::run('UPDATE users SET last_login_at = NOW() WHERE id = :id', [':id' => $id]);
    }

    public static function totalActive(): int
    {
        return (int) self::scalar('SELECT COUNT(*) FROM users WHERE active');
    }
}
