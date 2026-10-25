<?php
declare(strict_types=1);

/**
 * Punto de entrada único (Front Controller).
 * Rutas: index.php?r=controlador/acción
 *
 * Estructura del proyecto:
 *   frontend/public/  -> DocumentRoot de Apache (aquí vive este archivo)
 *   backend/          -> lógica de la aplicación (nunca es público)
 */

require dirname(__DIR__, 2) . '/backend/core/bootstrap.php';

// Sonda de salud para Render / Vercel / balanceadores.
// Comprueba que PHP responde y que la base de datos responde.
if (($_GET['r'] ?? '') === 'healthz' || (string) ($_SERVER['REQUEST_URI'] ?? '') === '/healthz') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    try {
        db()->query('SELECT 1');
        http_response_code(200);
        echo 'ok';
    } catch (\Throwable $e) {
        http_response_code(503);
        echo 'db-unavailable';
    }
    exit;
}

\App\Core\Router::run((string) ($_GET['r'] ?? 'dashboard/index'));
