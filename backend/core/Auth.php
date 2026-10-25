<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

/**
 * Autenticación basada en sesiones + control de acceso por roles.
 */
final class Auth
{
    /**
     * Intenta autenticar. Se acepta el nombre de usuario (campo principal)
     * o, como respaldo, el correo de la cuenta.
     */
    public static function attempt(string $identifier, string $password): bool
    {
        $identifier = trim($identifier);
        $user = Usuario::findByUsername($identifier);
        if ($user === null && str_contains($identifier, '@')) {
            $user = Usuario::findByEmail($identifier);
        }

        if (!$user || (int) $user['active'] !== 1) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT)) {
            Usuario::setPassword((int) $user['id'], $password);
        }

        session_regenerate_id(true);
        unset($user['password_hash']);
        // Auth::role() espera la clave 'role' (viene como role_code del JOIN).
        $user['role'] = $user['role_code'] ?? null;
        $_SESSION['auth'] = $user;
        Usuario::touchLogin((int) $user['id']);

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth']);
    }

    public static function user(): ?array
    {
        return $_SESSION['auth'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['auth']['id']) ? (int) $_SESSION['auth']['id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['auth']['role'] ?? null;
    }

    public static function name(): string
    {
        $u = self::user();
        return $u ? trim($u['first_name'] . ' ' . $u['last_name']) : '';
    }

    public static function hasRole(array|string $roles): bool
    {
        $roles = (array) $roles;
        return in_array(self::role(), $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('auth/login');
        }
        // Si la cuenta fue desactivada mientras tenía sesión abierta.
        $user = Usuario::find(self::id());
        if (!$user || (int) $user['active'] !== 1) {
            self::logout();
            redirect('auth/login', ['error' => 'cuenta']);
        }
    }

    /** Exige uno o varios roles; si no coincide, responde 403. */
    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            view('shared/error', [
                'code'    => 403,
                'title'   => 'Acceso denegado',
                'message' => 'No tienes permisos para acceder a este módulo.',
            ], 'layouts/auth');
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
