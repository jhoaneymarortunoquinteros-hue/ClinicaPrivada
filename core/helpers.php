<?php
declare(strict_types=1);

/**
 * Funciones helper globales (utilizadas desde controladores y vistas).
 */

function config(string $key, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = $GLOBALS['CONFIG'] ?? [];
    }
    $value = $cfg;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function db(): PDO
{
    return \App\Core\Database::pdo();
}

/** Escapa salida HTML (proyección contra XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Construye una URL interna: index.php?r=controlador/acción&params */
function url(string $route, array $params = []): string
{
    $base = rtrim((string) config('app.base_url', ''), '/');
    $query = array_merge(['r' => $route], $params);
    return $base . '/index.php?' . http_build_query($query);
}

function asset(string $path): string
{
    $base = rtrim((string) config('app.base_url', ''), '/');
    return $base . '/assets/' . ltrim($path, '/');
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

/**
 * Renderiza una vista. Si $layout no es null, el contenido se inyecta en el layout.
 */
function view(string $template, array $data = [], ?string $layout = 'layouts/app'): void
{
    $file = BASE_PATH . '/views/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Vista no encontrada: ' . $template);
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $file;
    $content = ob_get_clean();

    if ($layout !== null) {
        $title = $data['title'] ?? (string) config('app.name');
        require BASE_PATH . '/views/' . $layout . '.php';
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Lee un valor del POST (con fallback a GET). */
function input(string $key, mixed $default = ''): mixed
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function input_int(string $key, ?int $default = null): ?int
{
    $value = input($key, null);
    return is_numeric($value) ? (int) $value : $default;
}

function csrf_token(): string
{
    return (string) ($_SESSION['_csrf'] ?? '');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Mensajes flash. Con mensaje: guarda. Sin mensaje: lee y limpia.
 */
function flash(string $type, ?string $message = null): array
{
    if ($message !== null) {
        $_SESSION['flash'][$type][] = $message;
        return [];
    }
    $messages = $_SESSION['flash'][$type] ?? [];
    unset($_SESSION['flash'][$type]);
    return $messages;
}

/** Errores de validación (se leen una sola vez desde el formulario). */
function errors(): array
{
    $errors = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);
    return $errors;
}

function field_error(string $name): ?string
{
    $errors = $_SESSION['errors'] ?? [];
    return $errors[$name] ?? null;
}

/** Valores repoblados del último intento fallido de envío. */
function old(string $name, mixed $default = ''): mixed
{
    return $_SESSION['old'][$name] ?? $default;
}

/** Limpia el estado del formulario tras un envío exitoso. */
function clear_form_state(): void
{
    unset($_SESSION['old'], $_SESSION['errors']);
}

/** Guarda los datos enviados para repoblar el formulario en caso de error. */
function store_old(array $keys): void
{
    $old = [];
    foreach ($keys as $key) {
        $old[$key] = $_POST[$key] ?? '';
    }
    $_SESSION['old'] = $old;
}

/**
 * Guarda errores de validación y redirige de vuelta al formulario.
 */
function validation_fail(array $errors, array $oldKeys = [], string $route = null, array $params = []): never
{
    $_SESSION['errors'] = $errors;
    if ($oldKeys) {
        store_old($oldKeys);
    }
    $route = $route ?? ($_POST['_route'] ?? ($_GET['r'] ?? 'dashboard/index'));
    redirect($route, $params);
}

/**
 * Política de contraseñas de la aplicación.
 * Devuelve null si la contraseña es válida o, si no, el mensaje de error.
 *
 * Reglas: 8+ caracteres · 1 minúscula · 1 mayúscula · 1 número · 1 especial.
 */
function password_error(string $password, string $label = 'La contraseña'): ?string
{
    if (mb_strlen($password) < 8) {
        return $label . ' debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[a-záéíóúñ]/u', $password)) {
        return $label . ' debe incluir al menos una letra minúscula.';
    }
    if (!preg_match('/[A-ZÁÉÍÓÚÑ]/u', $password)) {
        return $label . ' debe incluir al menos una letra mayúscula.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return $label . ' debe incluir al menos un número.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return $label . ' debe incluir al menos un carácter especial (ej.: ! @ # $ % & *).';
    }
    return null;
}

/** Texto resumen de las reglas de contraseña (para mostrar junto al campo). */
function password_rules(): string
{
    return 'Mínimo 8 caracteres, con mayúscula, minúscula, número y un carácter especial.';
}

/**
 * Valida la sintaxis de un nombre de usuario (sin consultar la base de datos).
 * Devuelve null si es válido o el mensaje de error en caso contrario.
 */
function username_error(string $username, string $label = 'El nombre de usuario'): ?string
{
    $username = trim($username);
    if ($username === '') {
        return $label . ' es obligatorio.';
    }
    if (mb_strlen($username) < 3) {
        return $label . ' debe tener al menos 3 caracteres.';
    }
    if (mb_strlen($username) > 40) {
        return $label . ' no puede superar los 40 caracteres.';
    }
    if (!preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i', $username)) {
        return $label . ' solo admite letras, números, punto, guion y guion bajo (sin espacios ni acentos).';
    }
    return null;
}

/** Normaliza un texto para usarlo como nombre de usuario (slug). */
function username_slug(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = (string) preg_replace('/@.+$/u', '', $value);          // parte local del correo
    $value = (string) preg_replace('/[^a-z0-9]+/u', '-', $value);
    return trim((string) preg_replace('/^-+|-+$/u', '', $value), '-');
}

/** Badge de estado de cita: [etiqueta, clases CSS]. */
function status_badge(string $status): array
{
    return match ($status) {
        'programada'   => ['Programada', 'bg-amber-100 text-amber-800'],
        'confirmada'   => ['Confirmada', 'bg-blue-100 text-blue-800'],
        'atendida'     => ['Atendida', 'bg-emerald-100 text-emerald-800'],
        'cancelada'    => ['Cancelada', 'bg-rose-100 text-rose-800'],
        'inasistente'  => ['Inasistente', 'bg-slate-200 text-slate-700'],
        default        => [ucfirst($status), 'bg-slate-100 text-slate-700'],
    };
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function format_time(?string $time): string
{
    if (!$time) {
        return '—';
    }
    // PostgreSQL devuelve 09:15:00
    return substr($time, 0, 5);
}

function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}

/**
 * Extrae un mensaje legible de una excepción de base de datos.
 */
function db_error_message(\Throwable $e): string
{
    $message = $e instanceof \PDOException && isset($e->errorInfo[2])
        ? (string) $e->errorInfo[2]
        : $e->getMessage();
    $message = (string) preg_replace('/SQLSTATE\[[^\]]+\]:\s*[^,]*,\s*/', '', $message);
    $message = (string) preg_replace('/^ERROR:\s*\d+:\s*/', '', $message);
    $message = (string) preg_replace('/^ERROR:\s*/', '', $message);
    return trim($message) !== '' ? trim($message) : 'Error interno de base de datos.';
}

/** Fecha en español larga: "lunes, 13 de octubre de 2026". */
function fecha_larga(?string $date = null): string
{
    $ts = strtotime($date ?? 'today');
    if (!$ts) {
        return '';
    }
    $days = [
        'Sunday' => 'domingo', 'Monday' => 'lunes', 'Tuesday' => 'martes',
        'Wednesday' => 'miércoles', 'Thursday' => 'jueves', 'Friday' => 'viernes',
        'Saturday' => 'sábado',
    ];
    $months = [
        'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
        'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
        'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
        'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre',
    ];
    return sprintf(
        '%s, %d de %s de %d',
        $days[date('l', $ts)] ?? date('l', $ts),
        (int) date('j', $ts),
        $months[date('F', $ts)] ?? date('F', $ts),
        (int) date('Y', $ts)
    );
}

/**
 * Iconos SVG inline (Heroicons-like, 24x24).
 * Uso: echo icon('calendar', 'w-5 h-5');
 */
function icon(string $name, string $classes = 'w-5 h-5'): string
{
    $paths = [
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5.5a3.5 3.5 0 0 1 0 7"/><path d="M18.5 14.6c1.7.9 2.5 2.6 2.5 5.4"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'file'     => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
        'chart'    => '<path d="M4 20V12M10 20V5M16 20v-6M21 20H3"/>',
        'medical'  => '<path d="M12 5v14M5 12h14"/><rect x="3" y="3" width="18" height="18" rx="4"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.8-3.8"/>',
        'logout'   => '<path d="M9 5H5v14h4"/><path d="M15 8l4 4-4 4M19 12H9"/>',
        'menu'     => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'trash'    => '<path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16v4z"/><path d="M14 6l4 4"/>',
        'check'    => '<path d="M4 12l5 5L20 6"/>',
        'x'        => '<path d="M6 6l12 12M18 6L6 18"/>',
        'alert'    => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'eye'      => '<path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/>',
        'heart'    => '<path d="M12 20s-7-4.6-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.4-7 10-7 10z"/>',
    ];
    $inner = $paths[$name] ?? $paths['grid'];
    return '<svg class="' . e($classes) . '" fill="none" stroke="currentColor" stroke-width="1.7" '
        . 'stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">'
        . $inner . '</svg>';
}

/**
 * Pila de notificaciones flotantes (Toast) generada a partir de los mensajes
 * flash. Se imprime una sola vez por petición, al final del <body>.
 */
function toasts(): string
{
    $map = [
        'success' => ['toast-success', 'check'],
        'error'   => ['toast-error', 'alert'],
        'warning' => ['toast-warning', 'alert'],
        'info'    => ['toast-info', 'info'],
    ];

    $html = '';
    foreach ($map as $type => [$class, $ico]) {
        foreach (flash($type) as $message) {
            $html .= '<div class="toast ' . $class . '" data-auto-hide="5000" role="alert">'
                . '<span class="toast-icon">' . icon($ico, 'w-5 h-5') . '</span>'
                . '<span class="toast-body">' . e($message) . '</span>'
                . '<button type="button" class="toast-close" data-toast-close aria-label="Cerrar aviso">'
                . icon('x', 'w-4 h-4') . '</button>'
                . '</div>';
        }
    }

    if ($html === '') {
        return '';
    }

    return '<div class="toast-stack" aria-live="polite">' . $html . '</div>';
}

/** Calcula los datos de paginación. */
function paginate(int $total, int $page, int $perPage = 15): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    return [
        'page'       => $page,
        'per_page'   => $perPage,
        'total'      => $total,
        'total_pages'=> $totalPages,
        'offset'     => ($page - 1) * $perPage,
        'has_prev'   => $page > 1,
        'has_next'   => $page < $totalPages,
    ];
}
