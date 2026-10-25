<?php
/**
 * Página de error genérica (404, 403, etc.).
 * Variables: $code, $title, $message
 */
$code = $code ?? 404;
$title = $title ?? 'Error';
$message = $message ?? 'Ocurrió un problema inesperado.';
?>
<div class="card p-8 text-center shadow-xl">
    <div class="mx-auto grid place-items-center w-16 h-16 rounded-full bg-brand-50 text-brand-700 mb-4">
        <?= icon($code === 403 ? 'x' : 'search', 'w-8 h-8') ?>
    </div>
    <p class="text-5xl font-black text-brand-700 mb-2"><?= e((string) $code) ?></p>
    <h1 class="text-xl font-bold text-slate-900"><?= e($title) ?></h1>
    <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto"><?= e($message) ?></p>

    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
        <a href="<?= e(url('dashboard/index')) ?>"
           class="inline-flex justify-center items-center gap-2 bg-brand-700 hover:bg-brand-800 text-white
                  text-sm font-semibold rounded-lg px-5 py-2.5 transition">
            <?= icon('grid', 'w-4 h-4') ?> Volver a mi panel
        </a>
        <a href="<?= e(url('auth/login')) ?>"
           class="inline-flex justify-center items-center gap-2 bg-white border border-slate-300 hover:bg-slate-50
                  text-slate-700 text-sm font-semibold rounded-lg px-5 py-2.5 transition">
            Ir al inicio de sesión
        </a>
    </div>
</div>
