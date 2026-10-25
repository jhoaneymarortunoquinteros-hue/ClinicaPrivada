<?php
/**
 * Paginación estándar.
 * Variables: $pagination (array de paginate()), $extra (params adicionales de la URL)
 */
$pagination = $pagination ?? null;
$extra = $extra ?? [];

if (!$pagination || (int) $pagination['total_pages'] <= 1) {
    return;
}

$route = (string) ($_GET['r'] ?? 'dashboard/index');
$pageUrl = static function (int $page) use ($route, $extra): string {
    return url($route, array_merge($extra, ['page' => $page]));
};
?>
<nav class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4" aria-label="Paginación">
    <p class="text-xs text-slate-500">
        Página <strong><?= (int) $pagination['page'] ?></strong> de
        <strong><?= (int) $pagination['total_pages'] ?></strong>
        · <?= (int) $pagination['total'] ?> registro(s)
    </p>

    <div class="flex items-center gap-2">
        <?php if ($pagination['has_prev']): ?>
            <a href="<?= e($pageUrl((int) $pagination['page'] - 1)) ?>"
               class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-600
                      hover:bg-slate-50 transition">← Anterior</a>
        <?php else: ?>
            <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-300">← Anterior</span>
        <?php endif; ?>

        <?php if ($pagination['has_next']): ?>
            <a href="<?= e($pageUrl((int) $pagination['page'] + 1)) ?>"
               class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-sm text-slate-600
                      hover:bg-slate-50 transition">Siguiente →</a>
        <?php else: ?>
            <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-300">Siguiente →</span>
        <?php endif; ?>
    </div>
</nav>
