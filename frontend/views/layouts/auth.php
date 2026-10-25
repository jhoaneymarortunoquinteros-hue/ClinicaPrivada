<?php
/**
 * Layout para páginas públicas (login/registro/errores).
 * Variables disponibles: $title, $content
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Acceso') ?> · <?= e((string) config('app.name')) ?></title>
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
<body class="min-h-screen bg-gradient-to-br from-brand-900 via-brand-800 to-slate-900
             text-slate-800 antialiased flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Marca -->
        <a href="<?= e(url('auth/login')) ?>" class="flex items-center justify-center gap-3 mb-6 text-white">
            <span class="grid place-items-center w-11 h-11 rounded-xl bg-white/15"><?= icon('medical', 'w-7 h-7') ?></span>
            <span class="text-left leading-tight">
                <span class="block font-bold text-lg"><?= e((string) config('app.name')) ?></span>
                <span class="block text-xs text-white/60">Gestión de citas médicas</span>
            </span>
        </a>

        <!-- Alertas flash (se muestran como Toast) -->
        <?= toasts() ?>

        <?php if (!empty($notice)): ?>
            <div class="mb-4 border-l-4 border-amber-400 bg-amber-50 text-amber-800 rounded-r-lg px-4 py-3 text-sm shadow">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>

        <?= $content ?>

        <p class="mt-6 text-center text-xs text-white/50">
            © <?= e(date('Y')) ?> <?= e((string) config('app.name')) ?> · Clínica privada
        </p>
    </div>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
