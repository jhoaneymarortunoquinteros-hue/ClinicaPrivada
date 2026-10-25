<?php
/**
 * Gestión de citas (recepción / administración).
 * Variables del controlador (CitaController::index, rama admin/recepcionista):
 *  $appointments array filas de v_appointments
 *                 (id, scheduled_date, start_time, end_time, status, reason, notes,
 *                  patient_id, patient_name, patient_document, doctor_id, doctor_name,
 *                  specialty_name)
 *  $filters      array (q, status, doctor_id, date, date_from, date_to)
 *  $pagination   array resultado de paginate()
 *  $doctors      array opciones de médicos (id, full_name, specialty_name, duration_minutes)
 *  $statuses     array códigos de estado de \App\Models\Cita::STATUSES
 *  $summary      array conteo por estado + total para los filtros activos
 */

$appointments = $appointments ?? [];
$filters      = $filters ?? [];
$pagination   = $pagination ?? null;
$doctors      = $doctors ?? [];
$statuses     = $statuses ?? [];
$summary      = $summary ?? [];

$statusLabel = static function (string $code): string {
    return status_badge($code)[0];
};
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Citas médicas</h2>
        <p class="text-sm text-slate-500">Filtra la agenda, registra estados y cancela con motivo.</p>
    </div>
    <a href="<?= e(url('citas/crear')) ?>"
       class="inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg
              bg-brand-700 hover:bg-brand-800 text-white transition
              focus:outline-none focus:ring-2 focus:ring-brand-500">
        <?= icon('plus', 'w-4 h-4') ?>
        Programar cita
    </a>
</div>

<!-- Filtros -->
<div class="card p-4 mb-4">
    <form method="get" action="<?= e(url('citas/index')) ?>" class="space-y-4" novalidate>
        <input type="hidden" name="r" value="citas/index">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
            <div class="xl:col-span-2">
                <label for="q" class="input-label">Buscar</label>
                <input type="text" name="q" id="q" value="<?= e((string) ($filters['q'] ?? '')) ?>"
                       placeholder="Paciente, documento o motivo..." autocomplete="off"
                       class="input">
            </div>

            <div>
                <label for="status" class="input-label">Estado</label>
                <select name="status" id="status" class="input">
                    <option value="">Todos</option>
                    <?php foreach ($statuses as $code): ?>
                        <option value="<?= e($code) ?>"
                            <?= (string) ($filters['status'] ?? '') === $code ? 'selected' : '' ?>>
                            <?= e($statusLabel($code)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="doctor_id" class="input-label">Médico</label>
                <select name="doctor_id" id="doctor_id" class="input">
                    <option value="">Todos</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= e((string) $doctor['id']) ?>"
                            <?= (int) ($filters['doctor_id'] ?? 0) === (int) $doctor['id'] ? 'selected' : '' ?>>
                            <?= e($doctor['full_name']) ?> · <?= e($doctor['specialty_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="date" class="input-label">Fecha exacta</label>
                <input type="date" name="date" id="date" value="<?= e((string) ($filters['date'] ?? '')) ?>"
                       class="input">
            </div>

            <div>
                <label for="date_from" class="input-label">Desde</label>
                <input type="date" name="date_from" id="date_from"
                       value="<?= e((string) ($filters['date_from'] ?? '')) ?>" class="input">
            </div>

            <div>
                <label for="date_to" class="input-label">Hasta</label>
                <input type="date" name="date_to" id="date_to"
                       value="<?= e((string) ($filters['date_to'] ?? '')) ?>" class="input">
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <a href="<?= e(url('citas/index')) ?>"
               class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-semibold rounded-lg
                      border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                Limpiar
            </a>
            <button type="submit"
                    class="inline-flex justify-center items-center gap-2 px-6 py-2.5 text-sm font-semibold
                           rounded-lg bg-brand-700 hover:bg-brand-800 text-white transition
                           focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?= icon('search', 'w-4 h-4') ?> Buscar
            </button>
        </div>
    </form>
</div>

<!-- Resumen por estado -->
<div class="flex flex-wrap items-center gap-2 mb-4">
    <?php foreach ($statuses as $code): ?>
        <?php [$label, $badgeClasses] = status_badge($code); ?>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold <?= e($badgeClasses) ?>">
            <?= e($label) ?> · <?= (int) ($summary[$code] ?? 0) ?>
        </span>
    <?php endforeach; ?>
    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold bg-slate-900 text-white">
        Total: <?= (int) ($summary['total'] ?? 0) ?>
    </span>
</div>

<!-- Listado -->
<div class="card p-5">
    <div class="flex items-center gap-2 mb-4">
        <?= icon('calendar', 'w-5 h-5 text-brand-700') ?>
        <h2 class="text-sm font-bold text-slate-900">
            Resultados
            <span class="ml-1 text-slate-400 font-normal">(<?= (int) ($pagination['total'] ?? count($appointments)) ?>)</span>
        </h2>
    </div>

    <?php if (!$appointments): ?>
        <div class="py-10 text-center">
            <span class="grid place-items-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto mb-3">
                <?= icon('calendar', 'w-6 h-6') ?>
            </span>
            <p class="text-sm font-semibold text-slate-700">No se encontraron citas con los filtros seleccionados.</p>
            <a href="<?= e(url('citas/crear')) ?>"
               class="mt-3 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg
                      bg-brand-700 hover:bg-brand-800 text-white transition">
                <?= icon('plus', 'w-4 h-4') ?> Programar cita
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                        <th class="py-2 pr-4 font-semibold">Fecha</th>
                        <th class="py-2 pr-4 font-semibold">Horario</th>
                        <th class="py-2 pr-4 font-semibold">Paciente</th>
                        <th class="py-2 pr-4 font-semibold">Médico</th>
                        <th class="py-2 pr-4 font-semibold">Motivo</th>
                        <th class="py-2 pr-4 font-semibold">Estado</th>
                        <th class="py-2 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($appointments as $a): ?>
                        <?php
                        [$label, $badgeClasses] = status_badge((string) $a['status']);
                        $transitions = \App\Models\Cita::allowedTransitions((string) $a['status']);
                        ?>
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="py-2.5 pr-4 whitespace-nowrap text-slate-700">
                                <?= e(format_date($a['scheduled_date'])) ?>
                            </td>
                            <td class="py-2.5 pr-4 whitespace-nowrap font-semibold text-slate-800">
                                <?= e(format_time($a['start_time'])) ?> – <?= e(format_time($a['end_time'])) ?>
                            </td>
                            <td class="py-2.5 pr-4">
                                <span class="block font-semibold text-slate-800"><?= e($a['patient_name']) ?></span>
                                <span class="block text-xs text-slate-500">Doc. <?= e($a['patient_document']) ?></span>
                            </td>
                            <td class="py-2.5 pr-4">
                                <span class="block text-slate-700"><?= e($a['doctor_name']) ?></span>
                                <span class="block text-xs text-slate-500"><?= e($a['specialty_name']) ?></span>
                            </td>
                            <td class="py-2.5 pr-4 text-slate-600 max-w-[16rem]">
                                <?= e($a['reason'] ?: 'Sin motivo registrado') ?>
                                <?php if (!empty($a['notes'])): ?>
                                    <span class="block text-xs text-slate-400 line-clamp-2"><?= e($a['notes']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 pr-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= e($badgeClasses) ?>">
                                    <?= e($label) ?>
                                </span>
                            </td>
                            <td class="py-2.5">
                                <div class="flex flex-col items-start gap-2 min-w-[13rem]">
                                    <?php if ((string) $a['status'] !== 'atendida'): ?>
                                        <a href="<?= e(url('citas/reprogramar', ['id' => $a['id']])) ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                                  border border-slate-200 text-xs font-semibold text-slate-700
                                                  hover:bg-slate-50 transition">
                                            <?= icon('clock', 'w-4 h-4') ?> Reprogramar
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($transitions): ?>
                                        <form method="post" action="<?= e(url('citas/estado')) ?>"
                                              class="flex items-center gap-2"
                                              data-confirm="¿Aplicar el cambio de estado de esta cita?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string) $a['id']) ?>">
                                            <label for="status-<?= (int) $a['id'] ?>" class="sr-only">Nuevo estado</label>
                                            <select name="status" id="status-<?= (int) $a['id'] ?>" class="input text-xs py-1.5">
                                                <option value="">Cambiar estado…</option>
                                                <?php foreach ($transitions as $code): ?>
                                                    <option value="<?= e($code) ?>"><?= e($statusLabel($code)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white
                                                           bg-brand-700 hover:bg-brand-800 transition whitespace-nowrap">
                                                Aplicar
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php $appointment_id = (int) $a['id']; $extra = $filters; unset($extra['q']); ?>
                                    <?php include BASE_PATH . '/views/shared/cancel-form.php'; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$extra = array_filter($filters, static function ($value): bool {
    return $value !== '' && $value !== null && $value !== 0;
});
unset($extra['page']);
?>
<?php include BASE_PATH . '/views/shared/pagination.php'; ?>
