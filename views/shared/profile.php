<?php
/**
 * CU22 — Perfil personal de cualquier rol autenticado
 * (Administrador, Recepcionista y Médico).
 *
 * Variables del controlador (PerfilController::index):
 *  $user   array fila de users (id, role_id, role_name, username,
 *          email, first_name, last_name, phone, active, created_at)
 *  $errors array errores de validación por campo
 */

$user   = $user ?? [];
$errors = $errors ?? [];

/** Prioriza el valor repoblado (old) y, si no existe, el guardado en BD. */
$val = static function (string $field, mixed $default = '') use ($user): mixed {
    $old = old($field);
    return ($old !== '' && $old !== null) ? $old : ($user[$field] ?? $default);
};

$roleLabels = [
    'admin'         => 'Administrador',
    'recepcionista' => 'Recepcionista',
    'medico'        => 'Médico',
    'paciente'      => 'Paciente',
];
$roleCode   = (string) ($user['role_code'] ?? '');
$roleLabel  = $roleLabels[$roleCode] ?? (string) ($user['role_name'] ?? $roleCode);
$initials   = strtoupper(mb_substr((string) $val('first_name'), 0, 1)
    . strtoupper(mb_substr((string) $val('last_name'), 0, 1)));
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Mi perfil</h2>
        <p class="text-sm text-slate-500">
            Actualiza tus datos personales y, si lo deseas, tu contraseña.
        </p>
    </div>
    <a href="<?= e(url('dashboard/index')) ?>"
       class="inline-flex items-center gap-2 self-start px-4 py-2.5 rounded-lg border border-slate-300 bg-white
              text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
        ← Volver al panel
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    <!-- Formulario -->
    <div class="lg:col-span-2">
        <form method="post" action="<?= e(url('perfil/guardar')) ?>" class="space-y-5" novalidate>
            <?= csrf_field() ?>

            <!-- Datos personales -->
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <?= icon('user', 'w-5 h-5 text-brand-700') ?>
                    <h3 class="text-sm font-bold text-slate-900">Datos personales</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="input-label">Nombres *</label>
                        <input type="text" id="first_name" name="first_name" required maxlength="80"
                               value="<?= e((string) $val('first_name')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['first_name']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['first_name'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['first_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="last_name" class="input-label">Apellidos *</label>
                        <input type="text" id="last_name" name="last_name" required maxlength="80"
                               value="<?= e((string) $val('last_name')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['last_name']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['last_name'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['last_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="email" class="input-label">Correo electrónico *</label>
                        <input type="email" id="email" name="email" required maxlength="120"
                               value="<?= e((string) $val('email')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['email']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['email'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="phone" class="input-label">Teléfono</label>
                        <input type="tel" id="phone" name="phone" maxlength="30"
                               value="<?= e((string) $val('phone')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['phone']) ? 'true' : 'false' ?>"
                               placeholder="Ej.: 3001234567">
                        <?php if (!empty($errors['phone'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seguridad -->
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-1">
                    <?= icon('eye', 'w-5 h-5 text-brand-700') ?>
                    <h3 class="text-sm font-bold text-slate-900">Contraseña</h3>
                </div>
                <p class="text-xs text-slate-500 mb-4">
                    Déjalo en blanco si no quieres cambiarla. <?= password_rules() ?>
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="input-label">Nueva contraseña</label>
                        <input type="password" id="password" name="password" maxlength="72" autocomplete="new-password"
                               class="input" aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['password'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="confirm" class="input-label">Confirmar contraseña</label>
                        <input type="password" id="confirm" name="confirm" maxlength="72" autocomplete="new-password"
                               class="input" aria-invalid="<?= !empty($errors['confirm']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['confirm'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['confirm']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="<?= e(url('dashboard/index')) ?>"
                   class="px-5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm font-semibold
                          text-slate-600 hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-lg bg-brand-700 text-white text-sm font-semibold
                               hover:bg-brand-800 transition">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjeta de cuenta (solo lectura) -->
    <div class="card p-5">
        <div class="flex items-center gap-3 mb-4">
            <span class="grid place-items-center w-12 h-12 rounded-full bg-brand-700 text-white text-lg font-bold">
                <?= e($initials) ?>
            </span>
            <div class="leading-tight">
                <p class="font-bold text-slate-900">
                    <?= e(trim((string) $val('first_name') . ' ' . (string) $val('last_name'))) ?>
                </p>
                <span class="inline-flex items-center rounded-full bg-brand-50 text-brand-800 border
                             border-brand-100 px-2 py-0.5 text-[11px] font-semibold">
                    <?= e($roleLabel) ?>
                </span>
            </div>
        </div>

        <dl class="text-sm space-y-3">
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400">Nombre de usuario</dt>
                <dd class="font-semibold text-slate-700 break-all">@<?= e((string) ($user['username'] ?? '')) ?></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400">Estado de la cuenta</dt>
                <dd>
                    <?php if ((bool) ($user['active'] ?? true)): ?>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200
                                     text-emerald-700 px-2 py-0.5 text-xs font-semibold">
                            <?= icon('check', 'w-3.5 h-3.5') ?> Activa
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 border border-rose-200
                                     text-rose-700 px-2 py-0.5 text-xs font-semibold">
                            <?= icon('x', 'w-3.5 h-3.5') ?> Inactiva
                        </span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-400">Miembro desde</dt>
                <dd class="font-semibold text-slate-700">
                    <?= e(format_date(($user['created_at'] ?? null) !== null ? (string) $user['created_at'] : null)) ?>
                </dd>
            </div>
        </dl>

        <div class="mt-5 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-500 flex items-start gap-2">
                <?= icon('info', 'w-4 h-4 shrink-0 text-sky-600') ?>
                El nombre de usuario no se puede modificar; se usa para iniciar sesión.
            </p>
        </div>
    </div>
</div>
