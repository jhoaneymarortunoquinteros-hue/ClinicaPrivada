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

\App\Core\Router::run((string) ($_GET['r'] ?? 'dashboard/index'));
