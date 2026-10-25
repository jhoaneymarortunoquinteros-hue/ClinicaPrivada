<?php
/**
 * Reportes administrativos con filtrado dinámico (solo administrador).
 * Variables: $filters (date_from, date_to, doctor_id, specialty_id, status, date),
 *            $summary (claves de estado + total), $byDoctor, $bySpecialty,
 *            $agenda, $doctors, $specialties, $statuses
 */
$csvParams = [
    'date_from'    => $filters['date_from'],
    'date_to'      => $filters['date_to'],
    'doctor_id'    => $filters['doctor_id'],
    'specialty_id' => $filters['specialty_id'],
    'status'       => $filters['status'],
];
$pendientes = (int) ($summary['programada'] ?? 0) + (int) ($summary['confirmada'] ?? 0);
$statusLabels = [
    'programada' => 'Programadas', 'confirmada' => 'Confirmadas',
    'atendida' => 'Atendidas', 'cancelada' => 'Canceladas', 'inasistente' => 'Inasistentes',
];
?>
<!-- Filtros -->
<div class="card p-4 mb-5 no-print">
    <form method="get" action="<?= e(url('reportes/index')) ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input type="hidden" name="r" value="reportes/index">

        <div>
            <label for="date_from" class="input-label">Desde</label>
            <input type="date" id="date_from" name="date_from" value="<?= e($filters['date_from']) ?>" class="input">
        </div>
        <div>
            <label for="date_to" class="input-label">Hasta</label>
            <input type="date" id="date_to" name="date_to" value="<?= e($filters['date_to']) ?>" class="input">
        </div>
        <div>
            <label for="doctor_id" class="input-label">Médico</label>
            <select id="doctor_id" name="doctor_id" class="input">
                <option value="">Todos</option>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= e((string) $d['id']) ?>"
                        <?= (int) $filters['doctor_id'] === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= e($d['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="specialty_id" class="input-label">Especialidad</label>
            <select id="specialty_id" name="specialty_id" class="input">
                <option value="">Todas</option>
                <?php foreach ($specialties as $s): ?>
                    <option value="<?= e((string) $s['id']) ?>"
                        <?= (int) $filters['specialty_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="status" class="input-label">Estado</label>
            <select id="status" name="status" class="input">
                <option value="">Todos</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>>
                        <?= e($statusLabels[$st] ?? ucfirst($st)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="date" class="input-label">Agenda del día</label>
            <input type="date" id="date" name="date" value="<?= e($filters['date']) ?>" class="input">
        </div>

        <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap gap-2 justify-end">
            <button type="submit"
                    class="px-5 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
                Aplicar filtros
            </button>
            <a href="<?= e(url('reportes/index')) ?>"
               class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Limpiar
            </a>
            <a href="<?= e(url('reportes/csv', $csvParams)) ?>"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800
                      text-white text-sm font-semibold transition">
                <?= icon('file', 'w-4 h-4') ?> Descargar CSV
            </a>
        </div>
    </form>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php
    $cards = [
        ['Total de citas', (int) ($summary['total'] ?? 0), 'calendar', 'bg-brand-50 text-brand-700'],
        ['Atendidas', (int) ($summary['atendida'] ?? 0), 'check', 'bg-emerald-50 text-emerald-700'],
        ['Canceladas', (int) ($summary['cancelada'] ?? 0), 'x', 'bg-rose-50 text-rose-700'],
        ['Inasistentes', (int) ($summary['inasistente'] ?? 0), 'user', 'bg-slate-100 text-slate-600'],
        ['Pendientes', $pendientes, 'clock', 'bg-amber-50 text-amber-700'],
    ];
    foreach ($cards as [$label, $value, $ico, $color]): ?>
        <div class="card p-4 flex items-center gap-3">
            <span class="grid place-items-center w-10 h-10 rounded-xl shrink-0 <?= $color ?>">
                <?= icon($ico, 'w-5 h-5') ?>
            </span>
            <span>
                <span class="block text-xl font-bold text-slate-900"><?= (int) $value ?></span>
                <span class="block text-xs text-slate-500"><?= e($label) ?></span>
            </span>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <!-- Por médico -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="font-bold text-slate-900">Rendimiento por médico</h2>
        </div>
        <div class="table-responsive">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3 font-semibold">Médico</th>
                        <th class="px-3 py-3 text-right font-semibold">Total</th>
                        <th class="px-3 py-3 text-right font-semibold">Atend.</th>
                        <th class="px-3 py-3 text-right font-semibold">Canc.</th>
                        <th class="px-3 py-3 text-right font-semibold">Inasis.</th>
                        <th class="px-3 py-3 text-right font-semibold">Pend.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$byDoctor): ?>
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Sin datos en el rango seleccionado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($byDoctor as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <span class="block font-semibold text-slate-800"><?= e($row['doctor_name']) ?></span>
                                <span class="block text-xs text-slate-500"><?= e($row['specialty_name']) ?></span>
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-slate-900"><?= (int) $row['total'] ?></td>
                            <td class="px-3 py-3 text-right text-emerald-700"><?= (int) $row['atendidas'] ?></td>
                            <td class="px-3 py-3 text-right text-rose-600"><?= (int) $row['canceladas'] ?></td>
                            <td class="px-3 py-3 text-right text-slate-500"><?= (int) $row['inasistentes'] ?></td>
                            <td class="px-3 py-3 text-right text-amber-600"><?= (int) $row['pendientes'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Por especialidad -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="font-bold text-slate-900">Distribución por especialidad</h2>
        </div>
        <div class="table-responsive">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3 font-semibold">Especialidad</th>
                        <th class="px-3 py-3 text-right font-semibold">Citas</th>
                        <th class="px-3 py-3 text-right font-semibold">Atendidas</th>
                        <th class="text-left px-5 py-3 font-semibold">Proporción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$bySpecialty): ?>
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">Sin datos en el rango seleccionado.</td></tr>
                    <?php endif; ?>
                    <?php
                    $maxTotal = 1;
                    foreach ($bySpecialty as $row) {
                        $maxTotal = max($maxTotal, (int) $row['total']);
                    }
                    foreach ($bySpecialty as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-800"><?= e($row['specialty_name']) ?></td>
                            <td class="px-3 py-3 text-right font-bold text-slate-900"><?= (int) $row['total'] ?></td>
                            <td class="px-3 py-3 text-right text-emerald-700"><?= (int) $row['atendidas'] ?></td>
                            <td class="px-5 py-3">
                                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-brand-600"
                                         style="width: <?= (int) round(((int) $row['total'] * 100) / $maxTotal) ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Agenda diaria -->
<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap">
        <h2 class="font-bold text-slate-900">
            Agenda del día
            <?php if ($filters['date']): ?>
                <span class="text-sm font-normal text-slate-500">· <?= e(fecha_larga($filters['date'])) ?></span>
            <?php endif; ?>
        </h2>
        <?php if (!$filters['date']): ?>
            <p class="text-xs text-slate-500">Selecciona la pestaña “Agenda del día” con una fecha para verla.</p>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Hora</th>
                    <th class="text-left px-5 py-3 font-semibold">Médico</th>
                    <th class="text-left px-5 py-3 font-semibold">Paciente</th>
                    <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Motivo</th>
                    <th class="text-left px-5 py-3 font-semibold">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$agenda): ?>
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-400">
                            <?= $filters['date'] ? 'No hay citas para la fecha seleccionada.' : 'Elige una fecha para ver la agenda diaria.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($agenda as $slot): ?>
                    <?php [$label, $badgeClass] = status_badge($slot['status']); ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold text-slate-800">
                            <?= e(format_time($slot['start_time'])) ?> – <?= e(format_time($slot['end_time'])) ?>
                        </td>
                        <td class="px-5 py-3">
                            <span class="block text-slate-800"><?= e($slot['doctor_name']) ?></span>
                            <span class="block text-xs text-slate-500"><?= e($slot['specialty_name']) ?></span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="block text-slate-800"><?= e($slot['patient_name']) ?></span>
                            <span class="block text-xs text-slate-500">Doc. <?= e($slot['patient_document']) ?></span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 hidden md:table-cell"><?= e($slot['reason'] ?: '—') ?></td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $badgeClass ?>"><?= e($label) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
