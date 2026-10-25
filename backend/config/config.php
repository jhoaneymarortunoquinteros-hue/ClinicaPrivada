<?php
declare(strict_types=1);

/**
 * Configuración general de la aplicación.
 * Los valores pueden sobrescribirse con variables de entorno (DB_HOST, DB_PORT...)
 * o con el archivo opcional config/env.local.php (útil en Docker / nube).
 */

$local = is_file(__DIR__ . '/env.local.php') ? require __DIR__ . '/env.local.php' : [];

$config = [
    'app' => array_replace_recursive([
        'name'     => 'Clínica VidaSalud',
        'env'      => 'production',
        'debug'    => false,
        'base_url' => '',
        'timezone' => 'America/Bogota',
    ], $local['app'] ?? []),
    'db' => array_replace_recursive([
        'host'    => '127.0.0.1',
        'port'    => 5432,
        'name'    => 'clinica',
        'user'    => 'postgres',
        'pass'    => 'postgres',
        'charset' => 'utf8',
    ], $local['db'] ?? []),
    'session' => array_replace_recursive([
        'name'     => 'CLINICA_SESSION',
        'lifetime' => 7200,
    ], $local['session'] ?? []),
    // Correo saliente (recuperación de contraseña).
    // driver: 'smtp' (envío real) | 'log' (escribe en backend/storage/mail.log)
    'mail' => array_replace_recursive([
        'driver'     => 'smtp',
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',          // tls | ssl | none
        'user'       => '',
        'pass'       => '',
        'from'       => 'no-reply@localhost',
        'from_name'  => 'Clínica VidaSalud',
        'timeout'    => 25,
    ], $local['mail'] ?? []),
];

// Overrides desde variables de entorno (nube / Docker).
$envMap = [
    'DB_HOST' => 'host',
    'DB_PORT' => 'port',
    'DB_NAME' => 'name',
    'DB_USER' => 'user',
    'DB_PASS' => 'pass',
];
foreach ($envMap as $env => $key) {
    $value = getenv($env);
    if ($value !== false && $value !== '') {
        $config['db'][$key] = $key === 'port' ? (int) $value : $value;
    }
}

// Render / Neon suelen entregar una única URL: postgres://user:pass@host:port/db
$dbUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL');
if (is_string($dbUrl) && $dbUrl !== '') {
    $parts = parse_url($dbUrl);
    if (is_array($parts)) {
        $config['db']['host'] = $parts['host'] ?? $config['db']['host'];
        $config['db']['port'] = isset($parts['port']) ? (int) $parts['port'] : $config['db']['port'];
        $config['db']['name'] = ltrim((string) ($parts['path'] ?? ''), '/');
        if (isset($parts['user'])) {
            $config['db']['user'] = rawurldecode($parts['user']);
        }
        if (isset($parts['pass'])) {
            $config['db']['pass'] = rawurldecode($parts['pass']);
        }
    }
}

$mailEnvMap = [
    'MAIL_DRIVER'     => 'driver',
    'MAIL_HOST'       => 'host',
    'MAIL_PORT'       => 'port',
    'MAIL_ENCRYPTION' => 'encryption',
    'MAIL_USER'       => 'user',
    'MAIL_PASS'       => 'pass',
    'MAIL_FROM'       => 'from',
    'MAIL_FROM_NAME'  => 'from_name',
];
foreach ($mailEnvMap as $env => $key) {
    $value = getenv($env);
    if ($value !== false && $value !== '') {
        $config['mail'][$key] = $key === 'port' ? (int) $value : $value;
    }
}
$appName = getenv('APP_NAME');
if ($appName !== false && $appName !== '') {
    $config['app']['name'] = $appName;
}
$baseUrl = getenv('APP_BASE_URL');
if ($baseUrl !== false) {
    $config['app']['base_url'] = $baseUrl;
}

return $config;
