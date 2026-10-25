<?php
/**
 * Layout principal (autenticado).
 * Variables disponibles: $title (string), $content (string HTML)
 */
use App\Core\Auth;

$user = Auth::user();
$role = $user['role'] ?? '';
$route = (string) ($_GET['r'] ?? 'dashboard/index');
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$roleLabels = [
    'admin'         => 'Administrador',
    'recepcionista' => 'Recepcionista',
    'medico'        => 'Médico',
    'paciente'      => 'Paciente',
];

/** Menú según rol: [ruta, etiqueta, icono] */
$menus = [
    'admin' => [
        ['dashboard/index',          'Panel',           'grid'],
        ['usuarios/index',           'Usuarios',        'users'],
        ['medicos/index',            'Médicos',         'medical'],
        ['especialidades/index',     'Especialidades',  'heart'],
        ['pacientes/index',          'Pacientes',       'user'],
        ['citas/index',              'Citas',           'calendar'],
        ['reportes/index',           'Reportes',        'chart'],
        ['perfil/index',             'Mi perfil',       'user'],
    ],
    'recepcionista' => [
        ['dashboard/index',      'Panel',        'grid'],
        ['pacientes/index',      'Pacientes',    'users'],
        ['pacientes/crear',      'Nuevo paciente', 'plus'],
        ['citas/index',          'Citas',        'calendar'],
        ['citas/crear',          'Programar cita', 'plus'],
        ['perfil/index',         'Mi perfil',    'user'],
    ],
    'medico' => [
        ['dashboard/index',        'Panel',             'grid'],
        ['citas/index',            'Mi agenda',         'calendar'],
        ['historial/pacientes',    'Mis pacientes',     'users'],
        ['horarios/index',         'Horarios laborales','clock'],
        ['perfil/index',           'Mi perfil',         'user'],
    ],
    'paciente' => [
        ['dashboard/index',        'Panel',            'grid'],
        ['citas/crear',            'Solicitar cita',   'plus'],
        ['citas/index',            'Mis citas',        'calendar'],
        ['historial/miHistorial', 'Mi historial',     'file'],
        ['pacientes/perfil',       'Mi perfil',        'user'],
    ],
];
$menu = $menus[$role] ?? [];

function nav_active(string $route, string $item): bool
{
    if ($route === $item) {
        return true;
    }
    $routeParts = array_pad(explode('/', $route), 2, 'index');
    $itemParts = array_pad(explode('/', $item), 2, 'index');
    // Marca el módulo (acción "index") cuando la ruta pertenece al mismo controlador.
    return $routeParts[0] === $itemParts[0] && $itemParts[1] === 'index';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Panel') ?> · <?= e((string) config('app.name')) ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdfa', 100: '#ccfbf1', 600: '#0d9488',
                            700: '#0f766e', 800: '#115e59', 900: '#134e4a'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased flex flex-col lg:flex-row">

<!-- Overlay móvil -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden"></div>

<!-- Barra lateral -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-brand-900 text-white flex flex-col
              lg:sticky lg:top-0 lg:bottom-auto lg:left-auto lg:h-screen
              lg:translate-x-0 lg:shrink-0 lg:z-auto">
    <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
        <span class="grid place-items-center w-10 h-10 rounded-xl bg-brand-700 shrink-0"><?= icon('medical', 'w-6 h-6') ?></span>
        <div class="leading-tight">
            <p class="font-bold"><?= e((string) config('app.name')) ?></p>
            <p class="text-xs text-white/60">Gestión de citas</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <?php foreach ($menu as [$itemRoute, $label, $ico]): ?>
            <?php $active = nav_active($route, $itemRoute); ?>
            <a href="<?= e(url($itemRoute)) ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      <?= $active ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                <?= icon($ico, 'w-5 h-5 shrink-0') ?>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="px-3 py-4 border-t border-white/10">
        <form method="post" action="<?= e(url('auth/logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           text-white/70 hover:bg-rose-600 hover:text-white transition">
                <?= icon('logout', 'w-5 h-5') ?>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>

<!-- Contenido: columna que ocupa todo el espacio restante junto a la barra lateral -->
<div class="flex min-h-screen min-w-0 flex-1 flex-col">

    <!-- Barra superior -->
    <header class="sticky top-0 z-30 bg-white border-b border-slate-200 shadow-sm">
        <div class="flex items-center justify-between gap-4 px-4 sm:px-6 py-3">
            <div class="flex items-center gap-3">
                <button type="button" data-toggle-sidebar
                        class="lg:hidden p-2 rounded-lg hover:bg-slate-100" aria-label="Abrir menú">
                    <?= icon('menu', 'w-6 h-6') ?>
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-tight"><?= e($title ?? 'Panel') ?></h1>
                        <span class="hidden sm:inline-flex items-center rounded-full bg-brand-50 text-brand-800
                                     border border-brand-100 px-2 py-0.5 text-[11px] font-semibold leading-4">
                            <?= e($roleLabels[$role] ?? $role) ?>
                        </span>
                    </div>
                    <p class="hidden sm:block text-xs text-slate-500"><?= e(fecha_larga()) ?></p>
                </div>
            </div>

            <div class="relative">
                <button type="button" id="user-menu-button"
                        class="flex items-center gap-2 sm:gap-3 rounded-lg px-2 sm:px-3 py-2 hover:bg-slate-100 transition">
                    <span class="grid place-items-center w-9 h-9 rounded-full bg-brand-700 text-white text-sm font-bold">
                        <?= e(strtoupper(mb_substr($user['first_name'] ?? '?', 0, 1)) . strtoupper(mb_substr($user['last_name'] ?? '', 0, 1))) ?>
                    </span>
                    <span class="hidden sm:block text-left leading-tight">
                        <span class="block text-sm font-semibold text-slate-900"><?= e($fullName) ?></span>
                        <span class="block text-xs text-slate-500"><?= e($roleLabels[$role] ?? $role) ?></span>
                    </span>
                </button>
                <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-slate-200
                                            rounded-xl shadow-lg py-2 z-50">
                    <a href="<?= e(url('dashboard/index')) ?>"
                       class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Mi panel</a>
                    <?php // CU22: el perfil es accesible desde el menú para cualquier rol autenticado. ?>
                    <a href="<?= e(url($role === 'paciente' ? 'pacientes/perfil' : 'perfil/index')) ?>"
                       class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Mi perfil</a>
                    <form method="post" action="<?= e(url('auth/logout')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit"
                                class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main: absorbe toda la altura disponible (flex-1) para que el contenido
         ocupe el área completa, sin dejar huecos en la parte superior -->
    <main class="flex-1 w-full min-w-0 px-4 sm:px-6 py-6">
        <?= $content ?>
    </main>

    <footer class="px-6 py-4 text-center text-xs text-slate-400">
        © <?= e(date('Y')) ?> <?= e((string) config('app.name')) ?> · Sistema de gestión de citas médicas
    </footer>
</div>

<!-- Toasts de retroalimentación (se cierran solos) -->
<?= toasts() ?>

<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
