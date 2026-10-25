<?php
/**
 * Especialidades médicas (solo administrador).
 * Variables: $list (especialidades), $editing (especialidad|null),
 *            $formOpen (bool), $errors
 */
$editing = $editing ?? null;
$errors = $errors ?? [];
$val = static function (string $field, mixed $default = '') use ($editing): mixed {
    $old = old($field);
    if ($old !== '' && $old !== null) {
        return $old;
    }
    return $editing[$field] ?? $default;
};
?>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    <!-- Formulario -->
    <div class="lg:col-span-2">
        <div class="card p-5 border-l-4 border-brand-600">
            <h2 class="text-base font-bold text-slate-900 mb-4">
                <?= $editing ? 'Editar especialidad' : 'Nueva especialidad' ?>
                <?php if (!$editing): ?>
                    <a href="<?= e(url('especialidades/index', ['action' => 'nuevo'])) ?>"
                       class="ml-2 text-xs font-semibold text-brand-700 hover:underline hidden">nuevo</a>
                <?php endif; ?>
            </h2>

            <?php if (!$formOpen && !$editing): ?>
                <p class="text-sm text-slate-500 mb-4">
                    La duración definida aquí controla cuánto dura cada cita de esa especialidad.
                </p>
                <a href="<?= e(url('especialidades/index', ['action' => 'nuevo'])) ?>"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800
                          text-white text-sm font-semibold transition">
                    <?= icon('plus', 'w-4 h-4') ?> Crear especialidad
                </a>
            <?php else: ?>
                <form method="post" action="<?= e(url('especialidades/guardar')) ?>" class="space-y-4" novalidate>
                    <?= csrf_field() ?>
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= e((string) $editing['id']) ?>">
                    <?php endif; ?>

                    <div>
                        <label for="name" class="input-label">Nombre *</label>
                        <input type="text" id="name" name="name" required maxlength="80" class="input"
                               placeholder="Ej.: Endocrinología"
                               value="<?= e((string) $val('name')) ?>"
                               aria-invalid="<?= !empty($errors['name']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['name'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="duration_minutes" class="input-label">Duración de la consulta (minutos) *</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="5" max="240"
                               step="5" required class="input"
                               value="<?= e((string) $val('duration_minutes', 15)) ?>"
                               aria-invalid="<?= !empty($errors['duration_minutes']) ? 'true' : 'false' ?>">
                        <p class="mt-1 text-xs text-slate-500">
                            Entre 5 y 240 minutos. Todas las citas de esta especialidad durarán exactamente esto.
                        </p>
                        <?php if (!empty($errors['duration_minutes'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['duration_minutes']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="description" class="input-label">Descripción</label>
                        <textarea id="description" name="description" rows="2" maxlength="300"
                                  class="input"><?= e((string) $val('description')) ?></textarea>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="active" value="1"
                               class="rounded border-slate-300 text-brand-700 focus:ring-brand-600"
                            <?= (int) $val('active', 1) === 1 ? 'checked' : '' ?>>
                        Especialidad activa
                    </label>

                    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end pt-2">
                        <?php if ($editing): ?>
                            <a href="<?= e(url('especialidades/index')) ?>"
                               class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold
                                      text-slate-600 hover:bg-slate-50 text-center">
                                Cancelar
                            </a>
                        <?php endif; ?>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg
                                       bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
                            <?= icon('check', 'w-4 h-4') ?>
                            <?= $editing ? 'Guardar cambios' : 'Crear especialidad' ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Listado -->
    <div class="lg:col-span-3">
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Especialidades registradas</h2>
                <span class="text-xs text-slate-500"><?= count($list) ?> en total</span>
            </div>

            <div class="table-responsive">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-5 py-3 font-semibold">Especialidad</th>
                            <th class="text-left px-5 py-3 font-semibold">Duración</th>
                            <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Descripción</th>
                            <th class="text-left px-5 py-3 font-semibold">Estado</th>
                            <th class="text-right px-5 py-3 font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-400">
                                    Aún no hay especialidades registradas.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($list as $s): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-semibold text-slate-800"><?= e($s['name']) ?></td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-800">
                                        <?= (int) $s['duration_minutes'] ?> min
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-500 text-xs hidden md:table-cell">
                                    <?= e($s['description'] ?: '—') ?>
                                </td>
                                <td class="px-5 py-3">
                                    <?php if ((int) $s['active'] === 1): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Activa</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= e(url('especialidades/index', ['action' => 'editar', 'id' => $s['id']])) ?>"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border
                                                  border-slate-300 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                            <?= icon('edit', 'w-4 h-4') ?> Editar
                                        </a>
                                        <form method="post" action="<?= e(url('especialidades/eliminar')) ?>"
                                              data-confirm="¿Eliminar la especialidad «<?= e($s['name']) ?>»?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string) $s['id']) ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border
                                                           border-rose-200 text-xs font-semibold text-rose-600
                                                           hover:bg-rose-50">
                                                <?= icon('trash', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
