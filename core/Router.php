<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador simple: index.php?r=controlador/acción
 * Ej.: ?r=citas/crear  ->  App\Controllers\CitaController::crear()
 */
final class Router
{
    public static function run(string $route): void
    {
        $parts = array_pad(explode('/', trim($route, '/')), 2, null);
        $controller = preg_replace('/[^a-z0-9_]/i', '', (string) $parts[0]) ?: 'dashboard';
        $action = preg_replace('/[^a-z0-9_]/i', '', (string) $parts[1]) ?: 'index';

        // Las rutas usan el recurso en plural (citas, usuarios, especialidades...)
        // y los controladores en singular (CitaController, EspecialidadController...).
        $class = 'App\\Controllers\\' . ucfirst($controller) . 'Controller';
        if (!class_exists($class) && str_ends_with($controller, 's')) {
            // 'usuarios' -> 'usuario'; 'especialidades' -> 'especialidad' (quita 'es').
            $singulars = [substr($controller, 0, -1)];
            if (str_ends_with($controller, 'es')) {
                $singulars[] = substr($controller, 0, -2);
            }
            foreach ($singulars as $name) {
                $candidate = 'App\\Controllers\\' . ucfirst($name) . 'Controller';
                if (class_exists($candidate)) {
                    $class = $candidate;
                    break;
                }
            }
        }

        if (!class_exists($class)) {
            self::notFound();
        }

        $instance = new $class();

        if (!method_exists($instance, $action)) {
            self::notFound();
        }

        $reflection = new \ReflectionMethod($instance, $action);
        if (!$reflection->isPublic() || $reflection->isStatic()) {
            self::notFound();
        }

        $instance->{$action}();
    }

    private static function notFound(): never
    {
        http_response_code(404);
        view('shared/error', [
            'code'    => 404,
            'title'   => 'Página no encontrada',
            'message' => 'La ruta solicitada no existe o fue movida.',
        ], 'layouts/auth');
        exit;
    }
}
