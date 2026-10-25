<?php
/**
 * Horarios laborales del médico.
 * Variables: $medico (v_doctors), $week (0..6 => [label, blocks]),
 *            $canEdit (bool), $doctors (opciones para el admin), $errors
 */
$errors = $errors ?? [];
$dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
?>
<!-- Encabezado -->
<div class="card p-5 mb-5">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="font-bold text-slate-900"><?= e($medico['full_name']) ?></h2>
            <p class="text-sm text-slate-500">
                <?= e($medico['specialty_name']) ?> · Licencia <?= e($medico['license_number']) ?>
            </p>
            <p class="text-xs text-slate-500 mt-2 max-w-2xl">
                Este horario define los días y horas en los que los pacientes pueden reservar citas.
                Los días sin bloques quedan <strong>no disponibles</strong> para reservas.
            </p>
        </div>

        <?php if ($doctors): ?>
            <form method="get" action="<?= e(url('horarios/index')) ?>" class="flex items-end gap-2">
                <input type="hidden" name="r" value="horarios/index">
                <div>
                    <label for="doctor_id" class="input-label">Cambiar de médico</label>
                    <select id="doctor_id" name="doctor_id" class="input" onchange="this.form.submit()">
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= e((string) $d['id']) ?>"
                                <?= (int) $medico['id'] === (int) $d['id'] ? 'selected' : '' ?>>
                                <?= e($d['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <noscript>
                    <button type="submit" class="px-4 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold">
                        Ver
                    </button>
                </noscript>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($canEdit): ?>
    <!-- Agregar bloque -->
    <div class="card p-5 mb-5 border-l-4 border-brand-600">
        <h3 class="font-bold text-slate-900 mb-4">Agregar bloque horario</h3>

        <form method="post" action="<?= e(url('horarios/guardar')) ?>"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="doctor_id" value="<?= e((string) $medico['id']) ?>">

            <div class="lg:col-span-2 grid grid-cols-3 gap-3">
                <div class="col-span-3 sm:col-span-1">
                    <label for="weekday" class="input-label">Día *</label>
                    <select id="weekday" name="weekday" required class="input"
                            aria-invalid="<?= !empty($errors['weekday']) ? 'true' : 'false' ?>">
                        <option value="">—</option>
                        <?php foreach ($dayNames as $num => $name): ?>
                            <option value="<?= $num ?>"><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="start_time" class="input-label">Desde *</label>
                    <input type="time" id="start_time" name="start_time" required class="input"
                           aria-invalid="<?= !empty($errors['start_time']) ? 'true' : 'false' ?>">
                </div>
                <div>
                    <label for="end_time" class="input-label">Hasta *</label>
                    <input type="time" id="end_time" name="end_time" required class="input"
                           aria-invalid="<?= !empty($errors['end_time']) ? 'true' : 'false' ?>">
                </div>
            </div>

            <div class="lg:col-span-2">
                <?php foreach (['weekday', 'start_time', 'end_time'] as $f): ?>
                    <?php if (!empty($errors[$f])): ?>
                        <p class="text-xs text-rose-600 mb-1"><?= e($errors[$f]) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg
                               bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
                    <?= icon('plus', 'w-4 h-4') ?> Agregar bloque
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="mb-5 border-l-4 border-amber-400 bg-amber-50 text-amber-800 rounded-r-lg px-4 py-3 text-sm">
        Solo el médico propietario o el administrador pueden modificar este horario.
    </div>
<?php endif; ?>

<!-- Semana -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
    <?php foreach ($week as $num => $day): ?>
        <?php $hasBlocks = !empty($day['blocks']); ?>
        <div class="card overflow-hidden">
            <div class="px-3 py-2.5 <?= $hasBlocks ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-500' ?>">
                <p class="text-sm font-bold"><?= e($day['label']) ?></p>
                <p class="text-[11px] opacity-80">
                    <?= $hasBlocks ? count($day['blocks']) . ' bloque(s)' : 'No disponible' ?>
                </p>
            </div>

            <div class="p-3 space-y-2 min-h-[80px]">
                <?php if (!$hasBlocks): ?>
                    <p class="text-xs text-slate-400 text-center py-3">Sin citas este día</p>
                <?php endif; ?>

                <?php foreach ($day['blocks'] as $block): ?>
                    <div class="rounded-lg border <?= (int) $block['is_active'] === 1 ? 'border-brand-100 bg-brand-50' : 'border-slate-200 bg-slate-50 opacity-60' ?>
                                px-3 py-2">
                        <p class="text-xs font-bold text-slate-800">
                            <?= e(format_time($block['start_time'])) ?> – <?= e(format_time($block['end_time'])) ?>
                        </p>
                        <?php if ((int) $block['is_active'] !== 1): ?>
                            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                         bg-slate-200 text-slate-600">Pausado</span>
                        <?php endif; ?>

                        <?php if ($canEdit): ?>
                            <div class="flex items-center gap-1 mt-2">
                                <form method="post" action="<?= e(url('horarios/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e((string) $block['id']) ?>">
                                    <button type="submit"
                                            class="px-2 py-1 rounded text-[11px] font-semibold border border-slate-300
                                                   text-slate-600 hover:bg-white">
                                        <?= (int) $block['is_active'] === 1 ? 'Pausar' : 'Activar' ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= e(url('horarios/eliminar')) ?>"
                                      data-confirm="¿Eliminar este bloque horario?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e((string) $block['id']) ?>">
                                    <button type="submit"
                                            class="px-2 py-1 rounded text-[11px] font-semibold border border-rose-200
                                                   text-rose-600 hover:bg-rose-50">
                                        <?= icon('trash', 'w-3 h-3 inline') ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<p class="mt-4 text-xs text-slate-500">
    Nota: la duración de cada bloque debe ser múltiplo de la duración de las citas de la especialidad
    (<?= e($medico['specialty_name']) ?>: <?= (int) $medico['duration_minutes'] ?> min).
</p>
