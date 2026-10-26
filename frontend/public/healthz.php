<?php
declare(strict_types=1);

/**
 * Sonda de salud para Render / balanceadores.
 *
 * Vive como ARCHIVO REAL dentro del DocumentRoot, así que Apache la sirve
 * directamente: funciona aunque el rewrite (.htaccess) no estuviera activo,
 * que era exactamente el fallo que dejaba el servicio en 404.
 *
 * Devuelve 200 "ok" si PHP y PostgreSQL responden, 503 en caso contrario.
 */

require dirname(__DIR__, 2) . '/backend/core/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

try {
    db()->query('SELECT 1');
    http_response_code(200);
    echo 'ok';
} catch (Throwable $e) {
    http_response_code(503);
    echo 'db-unavailable';
}
