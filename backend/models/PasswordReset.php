<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Tokens de recuperación de contraseña.
 *
 * - En la BD solo se guarda el hash SHA-256 del token; el valor en claro
 *   únicamente viaja dentro del correo electrónico.
 * - Cada token es de un solo uso y caduca (60 minutos por defecto).
 */
final class PasswordReset extends Model
{
    public const LIFETIME_MINUTES = 60;

    /** Registra una solicitud de recuperación y devuelve el token en claro. */
    public static function issue(int $userId, ?string $ip = null): string
    {
        $token = bin2hex(random_bytes(32));

        self::run(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, ip_address)
             VALUES (:uid, :hash, NOW() + make_interval(mins => :mins), :ip)',
            [
                ':uid'  => $userId,
                ':hash' => hash('sha256', $token),
                ':mins' => self::LIFETIME_MINUTES,
                ':ip'   => ($ip !== null && $ip !== '') ? $ip : null,
            ]
        );

        return $token;
    }

    /** Devuelve la solicitud válida (no usada y no vencida) para ese token. */
    public static function findValid(string $token): ?array
    {
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return self::row(
            'SELECT r.id AS reset_id, r.user_id, r.expires_at,
                    u.username, u.email, u.first_name, u.active
               FROM password_resets r
               JOIN users u ON u.id = r.user_id
              WHERE r.token_hash = :hash
                AND r.used_at IS NULL
                AND r.expires_at > NOW()
                AND u.active = TRUE
              LIMIT 1',
            [':hash' => hash('sha256', $token)]
        );
    }

    /** Marca como usadas todas las solicitudes pendientes del usuario. */
    public static function consume(int $userId): void
    {
        self::run(
            'UPDATE password_resets
                SET used_at = NOW()
              WHERE user_id = :uid AND used_at IS NULL',
            [':uid' => $userId]
        );
    }

    /** Descarta solicitudes vencidas (limpieza ligera, sin bloquear). */
    public static function purgeExpired(): int
    {
        return self::run('DELETE FROM password_resets WHERE expires_at < NOW() - INTERVAL \'7 days\'');
    }
}
