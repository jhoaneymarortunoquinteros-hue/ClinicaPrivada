<?php
/**
 * Gestión de usuarios (solo administrador).
 * Variables: $users, $roles, $filters (q, role), $pagination,
 *            $editing (usuario|null), $formOpen (bool), $errors
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
$roleBadges = [
    'admin'         => 'bg-violet-100 text-violet-800',
    'recepcionista' => 'bg-sky-100 text-sky-800',
    'medico'        => 'bg-brand-100 text-brand-800',
    'paciente'      => 'bg-amber-100 text-amber-800',
];
?>
<!-- Buscador -->
<div class="card p-4 mb-5">
    <form method="get" action="<?= e(url('usuarios/index')) ?>" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="r" value="usuarios/index">
        <div class="flex-1">
            <label for="q" class="input-label">Buscar usuario</label>
            <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>"
                   placeholder="Nombre, apellido o correo..." class="input">
        </div>
        <div class="sm:w-56">
            <label for="role" class="input-label">Rol</label>
            <select id="role" name="role" class="input">
                <option value="">Todos los roles</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r['code']) ?>" <?= $filters['role'] === $r['code'] ? 'selected' : '' ?>>
                        <?= e($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                    class="px-5 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
                Buscar
            </button>
            <a href="<?= e(url('usuarios/index')) ?>"
               class="px-4 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-600 hover:bg-slate-50">
                Limpiar
            </a>
            <a href="<?= e(url('usuarios/index', ['action' => 'nuevo'])) ?>"
               class="ml-auto inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800
                      text-white text-sm font-semibold transition">
                <?= icon('plus', 'w-4 h-4') ?> Nuevo usuario
            </a>
        </div>
    </form>
</div>

<?php if ($formOpen): ?>
    <!-- Formulario crear / editar -->
    <div class="card p-5 mb-6 border-l-4 border-brand-600">
        <h2 class="text-base font-bold text-slate-900 mb-4">
            <?= $editing ? 'Editar usuario: ' . e($editing['first_name'] . ' ' . $editing['last_name']) : 'Nuevo usuario' ?>
        </h2>

        <form method="post" action="<?= e(url('usuarios/guardar')) ?>" class="space-y-4" novalidate>
            <?= csrf_field() ?>
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= e((string) $editing['id']) ?>">
                <input type="hidden" name="current_role_id" value="<?= e((string) $editing['role_id']) ?>">
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
                    <label for="role_id" class="input-label">Rol *</label>
                    <select id="role_id" name="role_id" required class="input"
                            aria-invalid="<?= !empty($errors['role_id']) ? 'true' : 'false' ?>">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= e((string) $r['id']) ?>"
                                <?= (int) $val('role_id', $editing['role_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['role_id'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['role_id']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="password" class="input-label">
                        Contraseña <?= $editing ? '<span class="font-normal text-slate-400">(opcional)</span>' : '*' ?>
                    </label>
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password"
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                           title="<?= e(password_rules()) ?>"
                           class="input" <?= $editing ? '' : 'required' ?>
                           placeholder="<?= $editing ? 'Dejar en blanco para mantener' : 'Mín. 8 caracteres' ?>"
                           aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>">
                    <p class="mt-1 text-xs text-slate-500"><?= e(password_rules()) ?></p>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="active" value="1" class="rounded border-slate-300 text-brand-700
                       focus:ring-brand-600"
                    <?= (int) $val('active', 1) === 1 ? 'checked' : '' ?>>
                Cuenta activa
            </label>

            <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end pt-2">
                <a href="<?= e(url('usuarios/index')) ?>"
                   class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600
                          hover:bg-slate-50 text-center">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-brand-700
                               hover:bg-brand-800 text-white text-sm font-semibold transition">
                    <?= icon('check', 'w-4 h-4') ?>
                    <?= $editing ? 'Guardar cambios' : 'Crear usuario' ?>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Listado -->
<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <h2 class="font-bold text-slate-900">Usuarios del sistema</h2>
        <span class="text-xs text-slate-500"><?= (int) $pagination['total'] ?> registro(s)</span>
    </div>

    <div class="table-responsive">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Usuario</th>
                    <th class="text-left px-5 py-3 font-semibold">Teléfono</th>
                    <th class="text-left px-5 py-3 font-semibold">Rol</th>
                    <th class="text-left px-5 py-3 font-semibold">Estado</th>
                    <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Último acceso</th>
                    <th class="text-right px-5 py-3 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$users): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                            No se encontraron usuarios con esos criterios.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid place-items-center w-9 h-9 rounded-full bg-brand-100
                                             text-brand-800 text-xs font-bold shrink-0">
                                    <?= e(strtoupper(mb_substr($u['first_name'], 0, 1) . mb_substr($u['last_name'], 0, 1))) ?>
                                </span>
                                <span>
                                    <span class="block font-semibold text-slate-800">
                                        <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                                    </span>
                                    <span class="block text-xs font-medium text-brand-700">
                                        @<?= e($u['username'] ?? 'sin-usuario') ?>
                                    </span>
                                    <?php if (!empty($u['email'])): ?>
                                        <span class="block text-xs text-slate-500"><?= e($u['email']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-600"><?= e($u['phone'] ?: '—') ?></td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $roleBadges[$u['role_code']] ?? 'bg-slate-100 text-slate-700' ?>">
                                <?= e($u['role_name']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <?php if ((int) $u['active'] === 1): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Activo</span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-slate-500 hidden md:table-cell text-xs">
                            <?= e(format_datetime($u['last_login_at'])) ?>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= e(url('usuarios/index', ['action' => 'editar', 'id' => $u['id']])) ?>"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300
                                          text-xs font-semibold text-slate-600 hover:bg-slate-100">
                                    <?= icon('edit', 'w-4 h-4') ?> Editar
                                </a>
                                <form method="post" action="<?= e(url('usuarios/toggle')) ?>"
                                      data-confirm="<?= (int) $u['active'] === 1 ? '¿Desactivar esta cuenta? El usuario no podrá iniciar sesión.' : '¿Activar esta cuenta?' ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e((string) $u['id']) ?>">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                                                   <?= (int) $u['active'] === 1
                                                       ? 'border border-rose-200 text-rose-600 hover:bg-rose-50'
                                                       : 'border border-emerald-200 text-emerald-700 hover:bg-emerald-50' ?>">
                                        <?= (int) $u['active'] === 1 ? 'Desactivar' : 'Activar' ?>
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
$extra = ['q' => $filters['q'], 'role' => $filters['role']];
include BASE_PATH . '/views/shared/pagination.php';
