<?php
/**
 * Pacientes con citas del médico en sesión.
 * Variables: $medico (v_doctors), $patients (filas), $q (búsqueda actual)
 */
$q = $q ?? '';
?>
<div class="card p-4 mb-5">
    <form method="get" action="<?= e(url('historial/pacientes')) ?>"
          class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <input type="hidden" name="r" value="historial/pacientes">
        <div class="flex-1">
            <label for="q" class="input-label">Buscar paciente</label>
            <input type="search" id="q" name="q" value="<?= e($q) ?>"
                   placeholder="Nombre o número de documento..." class="input">
        </div>
        <button type="submit"
                class="px-5 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
            Buscar
        </button>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="font-bold text-slate-900">Mis pacientes</h2>
            <p class="text-xs text-slate-500">Pacientes que tienen (o tuvieron) citas contigo</p>
        </div>
        <span class="text-xs text-slate-500"><?= count($patients) ?> paciente(s)</span>
    </div>

    <div class="table-responsive">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Paciente</th>
                    <th class="text-left px-5 py-3 font-semibold">Documento</th>
                    <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Contacto</th>
                    <th class="text-left px-5 py-3 font-semibold">Sangre</th>
                    <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Última visita</th>
                    <th class="text-left px-5 py-3 font-semibold">Citas</th>
                    <th class="text-right px-5 py-3 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$patients): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-400">
                            <?= $q !== '' ? 'No se encontraron pacientes con esa búsqueda.' : 'Aún no tienes pacientes con citas.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($patients as $p): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <span class="block font-semibold text-slate-800"><?= e($p['full_name']) ?></span>
                            <?php if (!empty($p['allergies']) && stripos($p['allergies'], 'ninguna') === false): ?>
                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[11px]
                                             font-semibold bg-rose-50 text-rose-700">
                                    <?= icon('x', 'w-3 h-3') ?> Alergia
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-slate-600"><?= e($p['document_number']) ?></td>
                        <td class="px-5 py-3 text-slate-500 text-xs hidden md:table-cell">
                            <span class="block"><?= e($p['phone'] ?: '—') ?></span>
                            <span class="block"><?= e($p['email']) ?></span>
                        </td>
                        <td class="px-5 py-3">
                            <?php if ($p['blood_type']): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                    <?= e($p['blood_type']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-slate-500 hidden md:table-cell text-xs">
                            <?= $p['last_visit'] ? e(format_date($p['last_visit'])) : '—' ?>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-800">
                                <?= (int) $p['appointments'] ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="<?= e(url('historial/ver', ['patient_id' => $p['id']])) ?>"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300
                                      text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                <?= icon('file', 'w-4 h-4') ?> Ver historial
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
