<?php
/**
 * Panel del administrador.
 * Variables del controlador:
 *  $stats         array (users, active_users, doctors, patients, today)
 *  $todaySummary  array (programada, confirmada, atendida, cancelada, inasistente, total)
 *  $monthSummary  array (mismas claves que $todaySummary)
 *  $byDoctor      array filas (doctor_name, specialty_name, total, atendidas, canceladas, inasistentes, pendientes)
 *  $recentUsers   array filas (first_name, last_name, email, role_name, active, created_at)
 */

$chipClass = static function (string $status): string {
    return status_badge($status)[1];
};
?>
<!-- Tarjetas KPI -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <a href="<?= e(url('usuarios/index')) ?>"
       class="card p-5 flex items-center gap-4 hover:border-brand-600 hover:shadow-md transition">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-brand-50 text-brand-700 shrink-0">
            <?= icon('users', 'w-6 h-6') ?>
        </span>
        <span>
            <span class="block text-2xl font-bold text-slate-900"><?= (int) ($stats['users'] ?? 0) ?></span>
            <span class="block text-sm text-slate-500">Usuarios registrados</span>
            <span class="block text-xs text-brand-700 font-semibold mt-0.5">
                <?= (int) ($stats['active_users'] ?? 0) ?> activo(s) · Ver listado
            </span>
        </span>
    </a>

    <a href="<?= e(url('medicos/index')) ?>"
       class="card p-5 flex items-center gap-4 hover:border-brand-600 hover:shadow-md transition">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-sky-50 text-sky-700 shrink-0">
            <?= icon('medical', 'w-6 h-6') ?>
        </span>
        <span>
            <span class="block text-2xl font-bold text-slate-900"><?= (int) ($stats['doctors'] ?? 0) ?></span>
            <span class="block text-sm text-slate-500">Médicos</span>
            <span class="block text-xs text-brand-700 font-semibold mt-0.5">Gestionar equipo</span>
        </span>
    </a>

    <a href="<?= e(url('pacientes/index')) ?>"
       class="card p-5 flex items-center gap-4 hover:border-brand-600 hover:shadow-md transition">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-violet-50 text-violet-700 shrink-0">
            <?= icon('user', 'w-6 h-6') ?>
        </span>
        <span>
            <span class="block text-2xl font-bold text-slate-900"><?= (int) ($stats['patients'] ?? 0) ?></span>
            <span class="block text-sm text-slate-500">Pacientes</span>
            <span class="block text-xs text-brand-700 font-semibold mt-0.5">Ver pacientes</span>
        </span>
    </a>

    <a href="<?= e(url('citas/index')) ?>"
       class="card p-5 flex items-center gap-4 hover:border-brand-600 hover:shadow-md transition">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-amber-50 text-amber-700 shrink-0">
            <?= icon('calendar', 'w-6 h-6') ?>
        </span>
        <span>
            <span class="block text-2xl font-bold text-slate-900"><?= (int) ($stats['today'] ?? 0) ?></span>
            <span class="block text-sm text-slate-500">Citas de hoy</span>
            <span class="block text-xs text-brand-700 font-semibold mt-0.5">Ver agenda</span>
        </span>
    </a>
</div>

<!-- Resumen de hoy y del mes -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="card p-5">
        <div class="flex items-center gap-2 mb-3">
            <?= icon('clock', 'w-5 h-5 text-brand-700') ?>
            <h2 class="text-sm font-bold text-slate-900">Resumen de hoy</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php foreach (['programada', 'confirmada', 'atendida', 'cancelada', 'inasistente'] as $status): ?>
                <?php [$label, $classes] = status_badge($status); ?>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold <?= e($classes) ?>">
                    <?= e($label) ?> · <?= (int) ($todaySummary[$status] ?? 0) ?>
                </span>
            <?php endforeach; ?>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold bg-slate-900 text-white">
                Total: <?= (int) ($todaySummary['total'] ?? 0) ?>
            </span>
        </div>
    </div>

    <div class="card p-5">
        <div class="flex items-center gap-2 mb-3">
            <?= icon('chart', 'w-5 h-5 text-brand-700') ?>
            <h2 class="text-sm font-bold text-slate-900">Resumen del mes</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php foreach (['programada', 'confirmada', 'atendida', 'cancelada', 'inasistente'] as $status): ?>
                <?php [, $classes] = status_badge($status); ?>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold <?= e($classes) ?>">
                    <?= (int) ($monthSummary[$status] ?? 0) ?>
                </span>
            <?php endforeach; ?>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold bg-slate-900 text-white">
                Total: <?= (int) ($monthSummary['total'] ?? 0) ?>
            </span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <!-- Rendimiento del mes por médico -->
    <div class="card p-5 xl:col-span-2">
        <div class="flex items-center gap-2 mb-4">
            <?= icon('chart', 'w-5 h-5 text-brand-700') ?>
            <h2 class="text-sm font-bold text-slate-900">Rendimiento del mes por médico</h2>
        </div>

        <?php if (!$byDoctor): ?>
            <p class="text-sm text-slate-500 py-6 text-center">No hay citas registradas este mes.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4 font-semibold">Médico</th>
                            <th class="py-2 pr-4 font-semibold">Especialidad</th>
                            <th class="py-2 pr-4 font-semibold text-center">Total</th>
                            <th class="py-2 pr-4 font-semibold text-center">Atendidas</th>
                            <th class="py-2 pr-4 font-semibold text-center">Canceladas</th>
                            <th class="py-2 pr-4 font-semibold text-center">Inasistentes</th>
                            <th class="py-2 font-semibold text-center">Pendientes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($byDoctor as $row): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 pr-4 font-semibold text-slate-800"><?= e($row['doctor_name']) ?></td>
                                <td class="py-2.5 pr-4 text-slate-500"><?= e($row['specialty_name']) ?></td>
                                <td class="py-2.5 pr-4 text-center font-bold text-slate-900"><?= (int) $row['total'] ?></td>
                                <td class="py-2.5 pr-4 text-center text-emerald-700"><?= (int) $row['atendidas'] ?></td>
                                <td class="py-2.5 pr-4 text-center text-rose-600"><?= (int) $row['canceladas'] ?></td>
                                <td class="py-2.5 pr-4 text-center text-slate-600"><?= (int) $row['inasistentes'] ?></td>
                                <td class="py-2.5 text-center text-amber-700"><?= (int) $row['pendientes'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <!-- Últimos usuarios -->
        <div class="card p-5">
            <div class="flex items-center gap-2 mb-4">
                <?= icon('users', 'w-5 h-5 text-brand-700') ?>
                <h2 class="text-sm font-bold text-slate-900">Últimos usuarios registrados</h2>
            </div>

            <?php if (!$recentUsers): ?>
                <p class="text-sm text-slate-500 py-4 text-center">Todavía no hay usuarios registrados.</p>
            <?php else: ?>
                <ul class="divide-y divide-slate-100">
                    <?php foreach ($recentUsers as $u): ?>
                        <li class="py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">
                                    <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                                </p>
                                <p class="text-xs text-slate-500 truncate"><?= e($u['email']) ?></p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-block rounded-full bg-brand-50 text-brand-800 px-2 py-0.5 text-xs font-semibold">
                                    <?= e($u['role_name']) ?>
                                </span>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    <?= e(format_datetime($u['created_at'])) ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Accesos rápidos -->
        <div class="card p-5">
            <h2 class="text-sm font-bold text-slate-900 mb-3">Accesos rápidos</h2>
            <div class="grid grid-cols-1 gap-2">
                <a href="<?= e(url('usuarios/index', ['action' => 'nuevo'])) ?>"
                   class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold
                          text-slate-700 hover:bg-slate-50 transition">
                    <?= icon('users', 'w-5 h-5 text-brand-700') ?> Usuarios
                </a>
                <a href="<?= e(url('medicos/index', ['action' => 'nuevo'])) ?>"
                   class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold
                          text-slate-700 hover:bg-slate-50 transition">
                    <?= icon('medical', 'w-5 h-5 text-brand-700') ?> Médicos
                </a>
                <a href="<?= e(url('reportes/index')) ?>"
                   class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold
                          text-slate-700 hover:bg-slate-50 transition">
                    <?= icon('chart', 'w-5 h-5 text-brand-700') ?> Reportes
                </a>
            </div>
        </div>
    </div>
</div>
