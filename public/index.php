<?php
declare(strict_types=1);

/**
 * Punto de entrada único (Front Controller).
 * Rutas: index.php?r=controlador/acción
 */

require dirname(__DIR__) . '/core/bootstrap.php';

\App\Core\Router::run((string) ($_GET['r'] ?? 'dashboard/index'));
