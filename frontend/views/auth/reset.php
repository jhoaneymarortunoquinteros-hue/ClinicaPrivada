<?php
/**
 * Nueva contraseña (público).
 * Variables del controlador (AuthController::restablecer):
 *  $token  string token de un solo uso recibido en el correo
 *  $name   string nombre del usuario dueño de la cuenta
 *  $errors array  errores de validación por campo
 */

$token  = (string) ($token ?? '');
$errors = $errors ?? [];
?>
<div class="card p-6 sm:p-8 shadow-xl">
    <div class="mb-6 text-center">
        <span class="grid place-items-center w-12 h-12 rounded-full bg-brand-50 text-brand-700 mx-auto mb-3">
            <?= icon('eye', 'w-6 h-6') ?>
        </span>
        <h1 class="text-xl font-bold text-slate-900">Crea una nueva contraseña</h1>
        <p class="text-sm text-slate-500 mt-1">
            Hola <?= e($name !== '' ? $name : 'usuario') ?>, define una contraseña nueva para tu cuenta.
        </p>
    </div>

    <form method="post" action="<?= e(url('auth/restablecer')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div>
            <label for="password" class="input-label">Nueva contraseña</label>
            <input type="password" id="password" name="password" required
                   autocomplete="new-password" autofocus
                   aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>"
                   class="input <?= !empty($errors['password']) ? 'border-rose-400' : '' ?>">
            <?php if (!empty($errors['password'])): ?>
                <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="confirm" class="input-label">Confirmar contraseña</label>
            <input type="password" id="confirm" name="confirm" required
                   autocomplete="new-password"
                   aria-invalid="<?= !empty($errors['confirm']) ? 'true' : 'false' ?>"
                   class="input <?= !empty($errors['confirm']) ? 'border-rose-400' : '' ?>">
            <?php if (!empty($errors['confirm'])): ?>
                <p class="mt-1 text-xs text-rose-600"><?= e($errors['confirm']) ?></p>
            <?php endif; ?>
        </div>

        <p class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-600">
            <?= password_rules() ?>
        </p>

        <button type="submit"
                class="w-full bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg
                       px-4 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-brand-500">
            Guardar nueva contraseña
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="<?= e(url('auth/login')) ?>" class="font-semibold text-brand-700 hover:underline">
            ← Volver a iniciar sesión
        </a>
    </p>
</div>
