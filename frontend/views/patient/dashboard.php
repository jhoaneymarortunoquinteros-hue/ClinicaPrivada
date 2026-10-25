<?php
/**
 * Panel del paciente.
 * Variables del controlador (DashboardController::paciente):
 *  $patient       array  fila v_patients (id, user_id, first_name, last_name, full_name,
 *                        document_number, birth_date, gender, blood_type, insurance, ...)
 *  $next         array  filas v_appointments próximas (id, scheduled_date, start_time,
 *                       end_time, status, reason, doctor_name, specialty_name)
 *  $history      array  filas v_appointments atendidas
 *  $latestRecord ?array fila v_medical_records más reciente (consultation_date, diagnosis,
 *                       doctor_name, specialty_name) o null
 */

/** Acepta tanto una sola fila (asociativa) como una lista de filas. */
$toList = static function ($value): array {
    if (!is_array($value) || $value === []) {
        return [];
    }
    return isset($value['id']) ? [$value] : array_values($value);
};

$nextRows        = $toList($next ?? []);
$historyRows     = $toList($history ?? []);
$nextAppointment = $nextRows[0] ?? null;
$latestRecord    = $latestRecord ?? null;

$btnPrimary   = 'inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-500';
$btnSecondary = 'inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 transition';
?>
<!-- Saludo + accesos -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Hola, <?= e($patient['first_name'] ?? '') ?></h2>
        <p class="text-sm text-slate-500 mt-1">Aquí tienes el resumen de tus citas y de tu historial clínico.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('citas/crear')) ?>" class="<?= e($btnPrimary) ?>">
            <?= icon('plus', 'w-4 h-4') ?> Solicitar cita
        </a>
        <a href="<?= e(url('historial/miHistorial')) ?>" class="<?= e($btnSecondary) ?>">
            <?= icon('file', 'w-4 h-4') ?> Ver mi historial
        </a>
    </div>
</div>

<!-- Próxima cita destacada -->
<?php if ($nextAppointment): ?>
    <div class="card p-5 sm:p-6 mb-6 border-l-4 border-brand-600">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <span class="grid place-items-center w-12 h-12 rounded-xl bg-brand-50 text-brand-700 shrink-0">
                    <?= icon('calendar', 'w-6 h-6') ?>
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tu próxima cita</p>
                    <p class="text-lg sm:text-xl font-bold text-slate-900">
                        <?= e(fecha_larga($nextAppointment['scheduled_date'])) ?>
                    </p>
                </div>
            </div>
            <?php [$statusLabel, $statusClasses] = status_badge((string) $nextAppointment['status']); ?>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= e($statusClasses) ?>">
                <?= e($statusLabel) ?>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3">
                <p class="text-xs font-semibold uppercase text-slate-400">Horario</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800">
                    <?= e(format_time($nextAppointment['start_time'])) ?> - <?= e(format_time($nextAppointment['end_time'])) ?>
                </p>
            </div>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3">
                <p class="text-xs font-semibold uppercase text-slate-400">Médico</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800"><?= e($nextAppointment['doctor_name'] ?? '—') ?></p>
            </div>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3">
                <p class="text-xs font-semibold uppercase text-slate-400">Especialidad</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800"><?= e($nextAppointment['specialty_name'] ?? '—') ?></p>
            </div>
        </div>

        <?php if (!empty($nextAppointment['reason'])): ?>
            <p class="text-sm text-slate-600 mb-4">
                <span class="font-semibold text-slate-700">Motivo:</span> <?= e($nextAppointment['reason']) ?>
            </p>
        <?php endif; ?>

        <a href="<?= e(url('citas/index')) ?>"
           class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
            Ver todas mis citas →
        </a>
    </div>
<?php else: ?>
    <div class="card p-8 mb-6 text-center">
        <span class="mx-auto grid place-items-center w-14 h-14 rounded-full bg-brand-50 text-brand-700">
            <?= icon('calendar', 'w-7 h-7') ?>
        </span>
        <h3 class="mt-4 text-base font-bold text-slate-900">Tu próxima cita</h3>
        <p class="mt-1 text-sm text-slate-500 max-w-md mx-auto">
            Aún no tienes citas programadas. Solicita una cita cuando necesites atención médica.
        </p>
        <div class="mt-5">
            <a href="<?= e(url('citas/crear')) ?>" class="<?= e($btnPrimary) ?>">
                <?= icon('plus', 'w-4 h-4') ?> Solicitar cita
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Tarjetas de resumen -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <a href="<?= e(url('citas/index')) ?>"
       class="card p-5 flex items-center gap-4 hover:border-brand-600 hover:shadow-md transition">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-brand-50 text-brand-700 shrink-0">
            <?= icon('calendar', 'w-6 h-6') ?>
        </span>
        <span>
            <span class="block text-2xl font-bold text-slate-900"><?= count($nextRows) ?></span>
            <span class="block text-sm text-slate-500">Próximas citas</span>
            <span class="block text-xs text-brand-700 font-semibold mt-0.5">Ver mis citas</span>
        </span>
    </a>

    <a href="<?= e(url('citas/index', ['status' => 'atendida'])) ?>"
       class="card p-5 flex items-center gap-4 hover:border-brand-600 hover:shadow-md transition">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 shrink-0">
            <?= icon('check', 'w-6 h-6') ?>
        </span>
        <span>
            <span class="block text-2xl font-bold text-slate-900"><?= count($historyRows) ?></span>
            <span class="block text-sm text-slate-500">Citas atendidas</span>
            <span class="block text-xs text-brand-700 font-semibold mt-0.5">Ver atendidas</span>
        </span>
    </a>

    <a href="<?= e(url('historial/miHistorial')) ?>"
       class="card p-5 flex items-center gap-4 hover:border-brand-600 hover:shadow-md transition">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-violet-50 text-violet-700 shrink-0">
            <?= icon('file', 'w-6 h-6') ?>
        </span>
        <span>
            <span class="block text-2xl font-bold text-slate-900">
                <?= $latestRecord ? e(format_date($latestRecord['consultation_date'])) : '—' ?>
            </span>
            <span class="block text-sm text-slate-500">Última evolución</span>
            <span class="block text-xs text-brand-700 font-semibold mt-0.5">Ver mi historial</span>
        </span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Tabla de próximas citas -->
    <div class="card p-5 lg:col-span-2">
        <div class="flex items-center gap-2 mb-4">
            <?= icon('clock', 'w-5 h-5 text-brand-700') ?>
            <h2 class="text-sm font-bold text-slate-900">Próximas citas</h2>
        </div>

        <?php if (!$nextRows): ?>
            <p class="text-sm text-slate-500 py-8 text-center">
                No tienes citas programadas por el momento.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4 font-semibold">Fecha</th>
                            <th class="py-2 pr-4 font-semibold">Horario</th>
                            <th class="py-2 pr-4 font-semibold">Médico</th>
                            <th class="py-2 pr-4 font-semibold">Especialidad</th>
                            <th class="py-2 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($nextRows as $a): ?>
                            <?php [$label, $classes] = status_badge((string) $a['status']); ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 pr-4 font-semibold text-slate-800">
                                    <?= e(format_date($a['scheduled_date'])) ?>
                                </td>
                                <td class="py-2.5 pr-4 text-slate-600">
                                    <?= e(format_time($a['start_time'])) ?> - <?= e(format_time($a['end_time'])) ?>
                                </td>
                                <td class="py-2.5 pr-4 text-slate-700"><?= e($a['doctor_name'] ?? '—') ?></td>
                                <td class="py-2.5 pr-4 text-slate-500"><?= e($a['specialty_name'] ?? '—') ?></td>
                                <td class="py-2.5">
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold <?= e($classes) ?>">
                                        <?= e($label) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Resumen de la última evolución -->
    <div class="card p-5">
        <div class="flex items-center gap-2 mb-4">
            <?= icon('heart', 'w-5 h-5 text-brand-700') ?>
            <h2 class="text-sm font-bold text-slate-900">Última evolución médica</h2>
        </div>

        <?php if ($latestRecord): ?>
            <p class="text-xs font-semibold uppercase text-slate-400">
                Consulta del <?= e(fecha_larga($latestRecord['consultation_date'])) ?>
            </p>
            <p class="mt-2 text-sm text-slate-700">
                <span class="font-semibold">Dr.:</span> <?= e($latestRecord['doctor_name'] ?? '—') ?>
                <span class="text-slate-400">·</span> <?= e($latestRecord['specialty_name'] ?? '—') ?>
            </p>
            <?php if (!empty($latestRecord['diagnosis'])): ?>
                <p class="mt-3 text-sm text-slate-600 whitespace-pre-line">
                    <span class="font-semibold text-slate-700">Diagnóstico:</span><br>
                    <?= e($latestRecord['diagnosis']) ?>
                </p>
            <?php endif; ?>
            <a href="<?= e(url('historial/miHistorial')) ?>"
               class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
                Ver historial completo →
            </a>
        <?php else: ?>
            <p class="text-sm text-slate-500">Aún no tienes evoluciones médicas.</p>
            <p class="mt-1 text-xs text-slate-400">
                Después de tu consulta, el médico registrará aquí el diagnóstico y la evolución.
            </p>
            <a href="<?= e(url('historial/miHistorial')) ?>"
               class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
                Ir a mi historial →
            </a>
        <?php endif; ?>
    </div>
</div>
