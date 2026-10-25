<?php
/**
 * Panel de recepción.
 * Variables del controlador (DashboardController::recepcionista):
 *  $todaySummary array claves programada, confirmada, atendida, cancelada, inasistente, total
 *  $patients     int   total de pacientes registrados
 *  $todayList    array filas de v_appointments de hoy
 *                 (id, scheduled_date, start_time, end_time, status, patient_name,
 *                  patient_document, doctor_name, specialty_name, reason)
 *  $upcoming     array filas de v_appointments próximas (mismas claves)
 */

$todaySummary = $todaySummary ?? [];
$todayList    = $todayList ?? [];
$upcoming     = $upcoming ?? [];
$patients     = (int) ($patients ?? 0);

$kpis = [
    [
        'label'  => 'Citas hoy',
        'value'  => (int) ($todaySummary['total'] ?? 0),
        'icon'   => 'calendar',
        'colors' => 'bg-amber-50 text-amber-700',
        'hint'   => 'Jornada en curso',
    ],
    [
        'label'  => 'Programadas',
        'value'  => (int) ($todaySummary['programada'] ?? 0),
        'icon'   => 'clock',
        'colors' => 'bg-sky-50 text-sky-700',
        'hint'   => 'Pendientes de confirmar',
    ],
    [
        'label'  => 'Confirmadas',
        'value'  => (int) ($todaySummary['confirmada'] ?? 0),
        'icon'   => 'check',
        'colors' => 'bg-blue-50 text-blue-700',
        'hint'   => 'Paciente avisado',
    ],
    [
        'label'  => 'Atendidas',
        'value'  => (int) ($todaySummary['atendida'] ?? 0),
        'icon'   => 'file',
        'colors' => 'bg-emerald-50 text-emerald-700',
        'hint'   => 'Consulta finalizada',
    ],
    [
        'label'  => 'Pacientes',
        'value'  => $patients,
        'icon'   => 'users',
        'colors' => 'bg-violet-50 text-violet-700',
        'hint'   => 'Registrados en el sistema',
    ],
];
?>
<!-- Accesos rápidos -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Buen día en recepción</h2>
        <p class="text-sm text-slate-500">Consulta el estado de la agenda y registra nuevos pacientes.</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
        <a href="<?= e(url('pacientes/crear')) ?>"
           class="inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg
                  border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition">
            <?= icon('plus', 'w-4 h-4') ?>
            Nuevo paciente
        </a>
        <a href="<?= e(url('citas/crear')) ?>"
           class="inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg
                 bg-brand-700 hover:bg-brand-800 text-white transition
                 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <?= icon('calendar', 'w-4 h-4') ?>
            Programar cita
        </a>
    </div>
</div>

<!-- Tarjetas KPI -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
    <?php foreach ($kpis as $kpi): ?>
        <div class="card p-5 flex items-center gap-4">
            <span class="grid place-items-center w-12 h-12 rounded-xl shrink-0 <?= e($kpi['colors']) ?>">
                <?= icon($kpi['icon'], 'w-6 h-6') ?>
            </span>
            <span class="min-w-0">
                <span class="block text-2xl font-bold text-slate-900"><?= (int) $kpi['value'] ?></span>
                <span class="block text-sm text-slate-500"><?= e($kpi['label']) ?></span>
                <span class="block text-xs text-slate-400 mt-0.5 truncate"><?= e($kpi['hint']) ?></span>
            </span>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <!-- Citas de hoy -->
    <div class="card p-5 xl:col-span-2">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2">
                <?= icon('calendar', 'w-5 h-5 text-brand-700') ?>
                <h2 class="text-sm font-bold text-slate-900">Citas de hoy</h2>
            </div>
            <a href="<?= e(url('citas/index', ['date' => date('Y-m-d')])) ?>"
               class="text-xs font-semibold text-brand-700 hover:underline">Ver agenda completa</a>
        </div>

        <?php if (!$todayList): ?>
            <div class="py-8 text-center">
                <span class="grid place-items-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto mb-3">
                    <?= icon('calendar', 'w-6 h-6') ?>
                </span>
                <p class="text-sm text-slate-500">No hay citas programadas para hoy.</p>
                <a href="<?= e(url('citas/crear')) ?>"
                   class="mt-3 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg
                          bg-brand-700 hover:bg-brand-800 text-white transition">
                    <?= icon('plus', 'w-4 h-4') ?> Programar la primera
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4 font-semibold">Horario</th>
                            <th class="py-2 pr-4 font-semibold">Paciente</th>
                            <th class="py-2 pr-4 font-semibold">Médico</th>
                            <th class="py-2 pr-4 font-semibold">Motivo</th>
                            <th class="py-2 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($todayList as $cita): ?>
                            <?php [$label, $badge] = status_badge((string) $cita['status']); ?>
                            <tr class="hover:bg-slate-50 align-top">
                                <td class="py-2.5 pr-4 whitespace-nowrap font-semibold text-slate-800">
                                    <?= e(format_time($cita['start_time'])) ?> – <?= e(format_time($cita['end_time'])) ?>
                                </td>
                                <td class="py-2.5 pr-4">
                                    <span class="block font-semibold text-slate-800"><?= e($cita['patient_name']) ?></span>
                                    <span class="block text-xs text-slate-500">Doc. <?= e($cita['patient_document']) ?></span>
                                </td>
                                <td class="py-2.5 pr-4">
                                    <span class="block text-slate-700"><?= e($cita['doctor_name']) ?></span>
                                    <span class="block text-xs text-slate-500"><?= e($cita['specialty_name']) ?></span>
                                </td>
                                <td class="py-2.5 pr-4 text-slate-600">
                                    <?= e($cita['reason'] ?: 'Sin motivo registrado') ?>
                                </td>
                                <td class="py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= e($badge) ?>">
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

    <!-- Próximas citas -->
    <div class="card p-5">
        <div class="flex items-center gap-2 mb-4">
            <?= icon('clock', 'w-5 h-5 text-brand-700') ?>
            <h2 class="text-sm font-bold text-slate-900">Próximas citas</h2>
        </div>

        <?php if (!$upcoming): ?>
            <p class="text-sm text-slate-500 py-6 text-center">No hay citas futuras agendadas.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($upcoming as $cita): ?>
                    <?php [$label, $badge] = status_badge((string) $cita['status']); ?>
                    <li class="py-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= e($cita['patient_name']) ?></p>
                            <p class="text-xs text-slate-500 truncate">
                                <?= e($cita['doctor_name']) ?> · <?= e($cita['specialty_name']) ?>
                            </p>
                            <p class="mt-1 text-xs font-semibold text-brand-700">
                                <?= e(format_date($cita['scheduled_date'])) ?>
                                · <?= e(format_time($cita['start_time'])) ?>
                            </p>
                        </div>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= e($badge) ?>">
                            <?= e($label) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= e(url('citas/index')) ?>"
               class="mt-3 inline-flex justify-center items-center w-full px-4 py-2.5 text-sm font-semibold
                      rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                Ver todas las citas
            </a>
        <?php endif; ?>
    </div>
</div>
