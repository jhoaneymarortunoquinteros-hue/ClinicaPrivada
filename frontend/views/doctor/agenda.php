<?php
/**
 * Agenda del médico por fecha.
 * Variables: $medico (v_doctors), $date (YYYY-MM-DD), $status (filtro),
 *            $appointments (citas del día), $todaySummary, $statuses
 */
$pendientes = (int) $todaySummary['programada'] + (int) $todaySummary['confirmada'];
$statusLabels = [
    'programada' => 'Programadas', 'confirmada' => 'Confirmadas',
    'atendida' => 'Atendidas', 'cancelada' => 'Canceladas', 'inasistente' => 'Inasistentes',
];
?>
<!-- Selector de fecha -->
<div class="card p-4 mb-5">
    <form method="get" action="<?= e(url('citas/index')) ?>"
          class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <input type="hidden" name="r" value="citas/index">
        <div>
            <label for="date" class="input-label">Fecha de la agenda</label>
            <input type="date" id="date" name="date" value="<?= e($date) ?>" class="input">
        </div>
        <div class="sm:w-52">
            <label for="status" class="input-label">Estado</label>
            <select id="status" name="status" class="input">
                <option value="">Todos</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>>
                        <?= e($statusLabels[$st] ?? ucfirst($st)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="px-5 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
                Ver día
            </button>
            <a href="<?= e(url('citas/index', ['date' => date('Y-m-d')])) ?>"
               class="px-4 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Hoy
            </a>
        </div>
    </form>
</div>

<!-- Resumen del día -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
    <div class="card px-4 py-3">
        <p class="text-xs text-slate-500">Total</p>
        <p class="text-lg font-bold text-slate-900"><?= (int) $todaySummary['total'] ?></p>
    </div>
    <div class="card px-4 py-3">
        <p class="text-xs text-slate-500">Pendientes</p>
        <p class="text-lg font-bold text-amber-600"><?= $pendientes ?></p>
    </div>
    <div class="card px-4 py-3">
        <p class="text-xs text-slate-500">Atendidas</p>
        <p class="text-lg font-bold text-emerald-700"><?= (int) $todaySummary['atendida'] ?></p>
    </div>
    <div class="card px-4 py-3">
        <p class="text-xs text-slate-500">Canceladas</p>
        <p class="text-lg font-bold text-rose-600"><?= (int) $todaySummary['cancelada'] ?></p>
    </div>
    <div class="card px-4 py-3">
        <p class="text-xs text-slate-500">Inasistentes</p>
        <p class="text-lg font-bold text-slate-500"><?= (int) $todaySummary['inasistente'] ?></p>
    </div>
</div>

<!-- Citas -->
<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="font-bold text-slate-900">Citas del <?= e(format_date($date)) ?></h2>
        <p class="text-xs text-slate-500"><?= e(fecha_larga($date)) ?></p>
    </div>

    <div class="table-responsive">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Hora</th>
                    <th class="text-left px-5 py-3 font-semibold">Paciente</th>
                    <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Motivo / Notas</th>
                    <th class="text-left px-5 py-3 font-semibold">Estado</th>
                    <th class="text-right px-5 py-3 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$appointments): ?>
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-400">
                            No hay citas para este día<?php echo $status !== '' ? ' con ese estado' : ''; ?>.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($appointments as $a): ?>
                    <?php [$label, $badgeClass] = status_badge($a['status']); ?>
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-5 py-3 font-semibold text-slate-800 whitespace-nowrap">
                            <?= e(format_time($a['start_time'])) ?> – <?= e(format_time($a['end_time'])) ?>
                        </td>
                        <td class="px-5 py-3">
                            <span class="block text-slate-800 font-semibold"><?= e($a['patient_name']) ?></span>
                            <span class="block text-xs text-slate-500">
                                Doc. <?= e($a['patient_document']) ?>
                                <?= $a['patient_blood_type'] ? ' · Sangre ' . e($a['patient_blood_type']) : '' ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 hidden md:table-cell">
                            <span class="block"><?= e($a['reason'] ?: '—') ?></span>
                            <?php if (!empty($a['notes'])): ?>
                                <span class="block text-xs text-slate-400 mt-0.5">Nota: <?= e($a['notes']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $badgeClass ?>"><?= e($label) ?></span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex flex-col items-end gap-2">
                                <a href="<?= e(url('historial/ver', ['patient_id' => $a['patient_id'], 'appointment_id' => $a['id']])) ?>"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300
                                          text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                    <?= icon('file', 'w-4 h-4') ?> Historial
                                </a>

                                <?php $transitions = \App\Models\Cita::allowedTransitions($a['status']); ?>
                                <?php if ($transitions): ?>
                                    <form method="post" action="<?= e(url('citas/estado')) ?>"
                                          class="flex items-center gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $a['id']) ?>">
                                        <select name="status" class="input !w-auto text-xs py-1.5">
                                            <?php foreach ($transitions as $t): ?>
                                                <option value="<?= e($t) ?>"><?= e(ucfirst($t)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-brand-700 hover:bg-brand-800
                                                       text-white text-xs font-semibold whitespace-nowrap">
                                            Aplicar
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($a['status'], ['programada', 'confirmada'], true)): ?>
                                    <?php $appointment_id = (int) $a['id']; $extra = ['date' => $date]; ?>
                                    <?php include FRONTEND_PATH . '/views/shared/cancel-form.php'; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
