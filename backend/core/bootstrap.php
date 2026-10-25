<?php
declare(strict_types=1);

/**
 * Bootstrap de la aplicación: configuración, autoload, sesión y CSRF.
 *
 * Rutas del proyecto:
 *   BASE_PATH     -> raíz del repositorio
 *   BACKEND_PATH  -> lógica (config, controllers, models, core, database)
 *   FRONTEND_PATH -> presentación (views, public)
 */

define('BASE_PATH', dirname(__DIR__, 2));
define('BACKEND_PATH', dirname(__DIR__));
define('FRONTEND_PATH', BASE_PATH . '/frontend');

$GLOBALS['CONFIG'] = require BACKEND_PATH . '/config/config.php';

require BACKEND_PATH . '/config/database.php';
require BACKEND_PATH . '/core/helpers.php';
require BACKEND_PATH . '/core/Auth.php';
require BACKEND_PATH . '/core/Router.php';

// Autoload modular: App\Models\*, App\Controllers\*, App\Core\*
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $rest = substr($class, strlen($prefix));
    $parts = explode('\\', $rest, 2);
    if (count($parts) < 2) {
        return;
    }
    [$namespace, $name] = $parts;
    $dirs = [
        'Models'      => '/models',
        'Controllers' => '/controllers',
        'Core'        => '/core',
    ];
    if (!isset($dirs[$namespace])) {
        return;
    }
    $file = BACKEND_PATH . $dirs[$namespace] . '/' . $name . '.php';
    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set((string) config('app.timezone', 'America/Bogota'));
ini_set('display_errors', config('app.debug') ? '1' : '0');
error_reporting(E_ALL);

// Sesión
session_name((string) config('session.name', 'CLINICA_SESSION'));
session_set_cookie_params([
    'lifetime' => (int) config('session.lifetime', 7200),
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

// Protección CSRF + limpieza de estado de formularios en peticiones POST.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    unset($_SESSION['old'], $_SESSION['errors']);
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesión expirada. Vuelve a cargar la página e intenta de nuevo.');
    }
}
