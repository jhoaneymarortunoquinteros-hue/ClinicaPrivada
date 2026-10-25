<?php
/**
 * Gestión de médicos con su especialidad (solo administrador).
 * Variables: $doctors, $specialties, $filters (q, specialty_id, active),
 *            $pagination, $editing (v_doctors|null), $editingUser (users|null),
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
<!-- Buscador -->
<div class="card p-4 mb-5">
    <form method="get" action="<?= e(url('medicos/index')) ?>" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="r" value="medicos/index">
        <div class="flex-1">
            <label for="q" class="input-label">Buscar médico</label>
            <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>"
                   placeholder="Nombre, correo o licencia..." class="input">
        </div>
        <div class="sm:w-56">
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
        <div class="sm:w-40">
            <label for="active" class="input-label">Estado</label>
            <select id="active" name="active" class="input">
                <option value="">Todos</option>
                <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Activos</option>
                <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                    class="px-5 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
                Buscar
            </button>
            <a href="<?= e(url('medicos/index', ['action' => 'nuevo'])) ?>"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800
                      text-white text-sm font-semibold transition">
                <?= icon('plus', 'w-4 h-4') ?> Nuevo médico
            </a>
        </div>
    </form>
</div>

<?php if ($formOpen): ?>
    <!-- Formulario crear / editar -->
    <div class="card p-5 mb-6 border-l-4 border-brand-600">
        <h2 class="text-base font-bold text-slate-900 mb-4">
            <?= $editing ? 'Editar médico: ' . e($editing['full_name']) : 'Registrar nuevo médico' ?>
        </h2>

        <form method="post" action="<?= e(url('medicos/guardar')) ?>" class="space-y-4" novalidate>
            <?= csrf_field() ?>
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= e((string) $editing['id']) ?>">
                <input type="hidden" name="user_id" value="<?= e((string) $editing['user_id']) ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="first_name" class="input-label">Nombres *</label>
                    <input type="text" id="first_name" name="first_name" required class="input"
                           value="<?= e((string) $val('first_name')) ?>"
                           aria-invalid="<?= !empty($errors['first_name']) ? 'true' : 'false' ?>">
                    <?php if (!empty($errors['first_name'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['first_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="last_name" class="input-label">Apellidos *</label>
                    <input type="text" id="last_name" name="last_name" required class="input"
                           value="<?= e((string) $val('last_name')) ?>"
                           aria-invalid="<?= !empty($errors['last_name']) ? 'true' : 'false' ?>">
                    <?php if (!empty($errors['last_name'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['last_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="email" class="input-label">Correo electrónico *</label>
                    <input type="email" id="email" name="email" required class="input"
                           value="<?= e((string) $val('email')) ?>"
                           aria-invalid="<?= !empty($errors['email']) ? 'true' : 'false' ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="phone" class="input-label">Teléfono</label>
                    <input type="tel" id="phone" name="phone" class="input"
                           value="<?= e((string) $val('phone')) ?>">
                </div>
                <div>
                    <label for="specialty_id" class="input-label">Especialidad *</label>
                    <select id="specialty_id" name="specialty_id" required class="input"
                            aria-invalid="<?= !empty($errors['specialty_id']) ? 'true' : 'false' ?>">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($specialties as $s): ?>
                            <option value="<?= e((string) $s['id']) ?>"
                                <?= (int) $val('specialty_id') === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['name']) ?> (<?= (int) $s['duration_minutes'] ?> min)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['specialty_id'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['specialty_id']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="license_number" class="input-label">N.º de licencia / colegiatura *</label>
                    <input type="text" id="license_number" name="license_number" required class="input"
                           placeholder="MED-1004" value="<?= e((string) $val('license_number')) ?>"
                           aria-invalid="<?= !empty($errors['license_number']) ? 'true' : 'false' ?>">
                    <?php if (!empty($errors['license_number'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['license_number']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="professional_title" class="input-label">Título profesional</label>
                    <input type="text" id="professional_title" name="professional_title" class="input"
                           placeholder="Ej.: Cardiólogo" value="<?= e((string) $val('professional_title')) ?>">
                </div>
                <div>
                    <label for="password" class="input-label">
                        Contraseña <?= $editing ? '<span class="font-normal text-slate-400">(opcional)</span>' : '*' ?>
                    </label>
                    <input type="password" id="password" name="password" minlength="8"
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                           title="<?= e(password_rules()) ?>"
                           autocomplete="new-password" class="input" <?= $editing ? '' : 'required' ?>
                           placeholder="<?= $editing ? 'Dejar en blanco para mantener' : 'Mínimo 8 caracteres' ?>"
                           aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>">
                    <p class="mt-1 text-xs text-slate-500"><?= e(password_rules()) ?></p>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="bio" class="input-label">Perfil profesional</label>
                    <textarea id="bio" name="bio" rows="2" maxlength="500"
                              class="input"><?= e((string) $val('bio')) ?></textarea>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="active" value="1"
                       class="rounded border-slate-300 text-brand-700 focus:ring-brand-600"
                    <?= (int) $val('active', 1) === 1 ? 'checked' : '' ?>>
                Médico activo (puede recibir citas)
            </label>

            <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end pt-2">
                <a href="<?= e(url('medicos/index')) ?>"
                   class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600
                          hover:bg-slate-50 text-center">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-brand-700
                               hover:bg-brand-800 text-white text-sm font-semibold transition">
                    <?= icon('check', 'w-4 h-4') ?>
                    <?= $editing ? 'Guardar cambios' : 'Registrar médico' ?>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Listado -->
<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <h2 class="font-bold text-slate-900">Equipo médico</h2>
        <span class="text-xs text-slate-500"><?= (int) $pagination['total'] ?> registro(s)</span>
    </div>

    <div class="table-responsive">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Médico</th>
                    <th class="text-left px-5 py-3 font-semibold">Licencia</th>
                    <th class="text-left px-5 py-3 font-semibold">Especialidad</th>
                    <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Duración</th>
                    <th class="text-left px-5 py-3 font-semibold">Estado</th>
                    <th class="text-right px-5 py-3 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$doctors): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                            No hay médicos registrados con esos criterios.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($doctors as $d): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <span class="block font-semibold text-slate-800"><?= e($d['full_name']) ?></span>
                            <span class="block text-xs text-slate-500"><?= e($d['email']) ?></span>
                        </td>
                        <td class="px-5 py-3 text-slate-600"><?= e($d['license_number']) ?></td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-800">
                                <?= e($d['specialty_name']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600 hidden md:table-cell">
                            <?= (int) $d['duration_minutes'] ?> min
                        </td>
                        <td class="px-5 py-3">
                            <?php if ((int) $d['active'] === 1 && (int) $d['user_active'] === 1): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Activo</span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                <a href="<?= e(url('medicos/index', ['action' => 'editar', 'id' => $d['id']])) ?>"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300
                                          text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                    <?= icon('edit', 'w-4 h-4') ?> Editar
                                </a>
                                <a href="<?= e(url('horarios/index', ['doctor_id' => $d['id']])) ?>"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300
                                          text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                    <?= icon('clock', 'w-4 h-4') ?> Horarios
                                </a>
                                <form method="post" action="<?= e(url('medicos/toggle')) ?>"
                                      data-confirm="<?= (int) $d['active'] === 1 ? '¿Desactivar este médico? No podrá recibir nuevas citas.' : '¿Activar este médico?' ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e((string) $d['id']) ?>">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                                                   <?= (int) $d['active'] === 1
                                                       ? 'border border-rose-200 text-rose-600 hover:bg-rose-50'
                                                       : 'border border-emerald-200 text-emerald-700 hover:bg-emerald-50' ?>">
                                        <?= (int) $d['active'] === 1 ? 'Desactivar' : 'Activar' ?>
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

<?php
$extra = ['q' => $filters['q'], 'specialty_id' => $filters['specialty_id'], 'active' => $filters['active']];
include BASE_PATH . '/views/shared/pagination.php';
