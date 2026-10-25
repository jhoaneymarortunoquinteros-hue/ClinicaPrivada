<?php
/**
 * Citas del paciente con filtros y paginación.
 * Variables del controlador (CitaController::index, rol paciente):
 *  $patient       array fila v_patients
 *  $appointments  array filas v_appointments (id, scheduled_date, start_time, end_time,
 *                        status, reason, doctor_name, specialty_name)
 *  $filters       array (status, date_from, date_to)
 *  $pagination    array de paginate() (page, total_pages, ...)
 *  $statuses      array estados posibles (Cita::STATUSES)
 */

$patient      = $patient ?? [];
$appointments = $appointments ?? [];
$filters      = ($filters ?? []) + ['status' => '', 'date_from' => '', 'date_to' => ''];
$hasFilters   = $filters['status'] !== '' || $filters['date_from'] !== '' || $filters['date_to'] !== '';

$btnPrimary = 'inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-500';
$btnSmall   = 'inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition';
?>
<!-- Encabezado -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Mis citas</h2>
        <p class="text-sm text-slate-500">
            Hola <?= e($patient['first_name'] ?? '') ?>, aquí puedes consultar y cancelar tus citas médicas.
        </p>
    </div>
    <a href="<?= e(url('citas/crear')) ?>" class="<?= e($btnPrimary) ?>">
        <?= icon('plus', 'w-4 h-4') ?> Solicitar cita
    </a>
</div>

<!-- Filtros -->
<div class="card p-4 mb-5">
    <form method="get" action="<?= e(url('citas/index')) ?>"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        <input type="hidden" name="r" value="citas/index">

        <div>
            <label for="status" class="input-label">Estado</label>
            <select id="status" name="status" class="input">
                <option value="">Todos los estados</option>
                <?php foreach ($statuses as $s): ?>
                    <?php [$statusLabel] = status_badge((string) $s); ?>
                    <option value="<?= e((string) $s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>>
                        <?= e($statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="date_from" class="input-label">Fecha desde</label>
            <input type="date" id="date_from" name="date_from"
                   value="<?= e($filters['date_from']) ?>" class="input">
        </div>

        <div>
            <label for="date_to" class="input-label">Fecha hasta</label>
            <input type="date" id="date_to" name="date_to"
                   value="<?= e($filters['date_to']) ?>" class="input">
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="<?= e($btnPrimary) ?>">
                <?= icon('search', 'w-4 h-4') ?> Filtrar
            </button>
            <a href="<?= e(url('citas/index')) ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 bg-white
                      text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                <?= icon('x', 'w-4 h-4') ?> Limpiar
            </a>
        </div>
    </form>
</div>

<!-- Listado -->
<div class="card p-5">
    <div class="flex items-center gap-2 mb-4">
        <?= icon('calendar', 'w-5 h-5 text-brand-700') ?>
        <h2 class="text-sm font-bold text-slate-900">Mis citas</h2>
        <span class="ml-auto text-xs text-slate-500"><?= (int) ($pagination['total'] ?? count($appointments)) ?> cita(s)</span>
    </div>

    <?php if (!$appointments): ?>
        <div class="py-10 text-center">
            <span class="mx-auto grid place-items-center w-14 h-14 rounded-full bg-slate-100 text-slate-400">
                <?= icon('calendar', 'w-7 h-7') ?>
            </span>
            <?php if ($hasFilters): ?>
                <p class="mt-4 text-sm font-semibold text-slate-600">
                    No hay citas que coincidan con los filtros seleccionados.
                </p>
                <p class="mt-1 text-xs text-slate-400">Prueba con otro estado u otro rango de fechas.</p>
                <div class="mt-4">
                    <a href="<?= e(url('citas/index')) ?>"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 bg-white
                              text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Limpiar filtros
                    </a>
                </div>
            <?php else: ?>
                <p class="mt-4 text-sm font-semibold text-slate-600">Aún no tienes citas registradas.</p>
                <p class="mt-1 text-xs text-slate-400">Solicita tu primera cita en pocos pasos.</p>
                <div class="mt-4">
                    <a href="<?= e(url('citas/crear')) ?>" class="<?= e($btnPrimary) ?>">
                        <?= icon('plus', 'w-4 h-4') ?> Solicitar cita
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                        <th class="py-2 pr-4 font-semibold">Fecha</th>
                        <th class="py-2 pr-4 font-semibold">Horario</th>
                        <th class="py-2 pr-4 font-semibold">Médico</th>
                        <th class="py-2 pr-4 font-semibold">Especialidad</th>
                        <th class="py-2 pr-4 font-semibold">Motivo</th>
                        <th class="py-2 pr-4 font-semibold">Estado</th>
                        <th class="py-2 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($appointments as $a): ?>
                        <?php [$label, $classes] = status_badge((string) $a['status']); ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-semibold text-slate-800 whitespace-nowrap">
                                <?= e(format_date($a['scheduled_date'])) ?>
                            </td>
                            <td class="py-3 pr-4 text-slate-600 whitespace-nowrap">
                                <?= e(format_time($a['start_time'])) ?> - <?= e(format_time($a['end_time'])) ?>
                            </td>
                            <td class="py-3 pr-4 text-slate-700"><?= e($a['doctor_name'] ?? '—') ?></td>
                            <td class="py-3 pr-4 text-slate-500"><?= e($a['specialty_name'] ?? '—') ?></td>
                            <td class="py-3 pr-4 text-slate-500 max-w-xs truncate"
                                title="<?= e((string) ($a['reason'] ?? '')) ?>">
                                <?= e(($a['reason'] ?? '') !== '' ? $a['reason'] : '—') ?>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold <?= e($classes) ?>">
                                    <?= e($label) ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if (in_array($a['status'], ['programada', 'confirmada'], true)): ?>
                                        <?php $appointment_id = (int) $a['id']; $extra = ['status' => $filters['status']]; ?>
                                        <?php include BASE_PATH . '/views/shared/cancel-form.php'; ?>
                                    <?php elseif ($a['status'] === 'atendida'): ?>
                                        <a href="<?= e(url('historial/miHistorial')) ?>"
                                           class="<?= e($btnSmall) ?> border border-brand-200 text-brand-700 hover:bg-brand-50">
                                            <?= icon('file', 'w-4 h-4') ?> Ver evolución
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        $extra = ['status' => $filters['status'], 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']];
        include BASE_PATH . '/views/shared/pagination.php';
        ?>
    <?php endif; ?>
</div>
