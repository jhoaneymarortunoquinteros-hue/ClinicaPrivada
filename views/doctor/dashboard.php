<?php
/**
 * Panel del médico.
 * Variables: $medico (v_doctors), $todayList (citas de hoy),
 *            $todaySummary, $weekSummary (claves de estado + total)
 */
$statusesToday = $todaySummary;
$pendientes = (int) $statusesToday['programada'] + (int) $statusesToday['confirmada'];
?>
<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4 flex items-center gap-3">
        <span class="grid place-items-center w-10 h-10 rounded-xl bg-brand-50 text-brand-700 shrink-0">
            <?= icon('calendar', 'w-5 h-5') ?>
        </span>
        <span>
            <span class="block text-xl font-bold text-slate-900"><?= (int) $statusesToday['total'] ?></span>
            <span class="block text-xs text-slate-500">Citas hoy</span>
        </span>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <span class="grid place-items-center w-10 h-10 rounded-xl bg-amber-50 text-amber-700 shrink-0">
            <?= icon('clock', 'w-5 h-5') ?>
        </span>
        <span>
            <span class="block text-xl font-bold text-slate-900"><?= $pendientes ?></span>
            <span class="block text-xs text-slate-500">Pendientes hoy</span>
        </span>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <span class="grid place-items-center w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 shrink-0">
            <?= icon('check', 'w-5 h-5') ?>
        </span>
        <span>
            <span class="block text-xl font-bold text-slate-900"><?= (int) $statusesToday['atendida'] ?></span>
            <span class="block text-xs text-slate-500">Atendidas hoy</span>
        </span>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <span class="grid place-items-center w-10 h-10 rounded-xl bg-sky-50 text-sky-700 shrink-0">
            <?= icon('chart', 'w-5 h-5') ?>
        </span>
        <span>
            <span class="block text-xl font-bold text-slate-900"><?= (int) $weekSummary['atendida'] ?></span>
            <span class="block text-xs text-slate-500">Atendidas (7 días)</span>
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <!-- Agenda de hoy -->
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h2 class="font-bold text-slate-900">Agenda de hoy</h2>
                <p class="text-xs text-slate-500"><?= e(fecha_larga()) ?> · <?= e($medico['specialty_name']) ?></p>
            </div>
            <a href="<?= e(url('citas/index', ['date' => date('Y-m-d')])) ?>"
               class="text-sm font-semibold text-brand-700 hover:underline">Ver agenda completa →</a>
        </div>

        <div class="table-responsive">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3 font-semibold">Hora</th>
                        <th class="text-left px-5 py-3 font-semibold">Paciente</th>
                        <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Motivo</th>
                        <th class="text-left px-5 py-3 font-semibold">Estado</th>
                        <th class="text-right px-5 py-3 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$todayList): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-400">
                                No tienes citas programadas para hoy.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($todayList as $a): ?>
                        <?php [$label, $badgeClass] = status_badge($a['status']); ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-800">
                                <?= e(format_time($a['start_time'])) ?> – <?= e(format_time($a['end_time'])) ?>
                            </td>
                            <td class="px-5 py-3">
                                <span class="block text-slate-800"><?= e($a['patient_name']) ?></span>
                                <span class="block text-xs text-slate-500">Doc. <?= e($a['patient_document']) ?></span>
                            </td>
                            <td class="px-5 py-3 text-slate-500 hidden md:table-cell"><?= e($a['reason'] ?: '—') ?></td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $badgeClass ?>"><?= e($label) ?></span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
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
                                                           text-white text-xs font-semibold">
                                                Aplicar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resumen -->
    <div class="space-y-5">
        <div class="card p-5">
            <h3 class="font-bold text-slate-900 mb-3">Resumen de la semana</h3>
            <ul class="space-y-2 text-sm">
                <?php
                $rows = [
                    ['Programadas', $weekSummary['programada'], 'bg-amber-100 text-amber-800'],
                    ['Confirmadas', $weekSummary['confirmada'], 'bg-blue-100 text-blue-800'],
                    ['Atendidas', $weekSummary['atendida'], 'bg-emerald-100 text-emerald-800'],
                    ['Canceladas', $weekSummary['cancelada'], 'bg-rose-100 text-rose-800'],
                    ['Inasistentes', $weekSummary['inasistente'], 'bg-slate-200 text-slate-700'],
                ];
                foreach ($rows as [$lbl, $count, $cls]): ?>
                    <li class="flex items-center justify-between">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $cls ?>"><?= e($lbl) ?></span>
                        <span class="font-bold text-slate-900"><?= (int) $count ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card p-5">
            <h3 class="font-bold text-slate-900 mb-3">Accesos rápidos</h3>
            <div class="space-y-2">
                <a href="<?= e(url('citas/index', ['date' => date('Y-m-d')])) ?>"
                   class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold
                          text-slate-700 hover:bg-slate-50 transition">
                    <?= icon('calendar', 'w-5 h-5 text-brand-700') ?> Mi agenda diaria
                </a>
                <a href="<?= e(url('historial/pacientes')) ?>"
                   class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold
                          text-slate-700 hover:bg-slate-50 transition">
                    <?= icon('users', 'w-5 h-5 text-brand-700') ?> Mis pacientes
                </a>
                <a href="<?= e(url('horarios/index')) ?>"
                   class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold
                          text-slate-700 hover:bg-slate-50 transition">
                    <?= icon('clock', 'w-5 h-5 text-brand-700') ?> Horarios laborales
                </a>
            </div>
        </div>
    </div>
</div>
