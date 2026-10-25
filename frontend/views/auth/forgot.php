<?php
/**
 * Recuperación de contraseña (público).
 * Variables del controlador: $title, $errors
 * El envío del correo está simulado: siempre se responde con el mismo mensaje.
 */
$errors = $errors ?? [];
?>
<div class="card p-6 sm:p-8 shadow-xl">
    <div class="mb-6 text-center">
        <span class="grid place-items-center w-12 h-12 rounded-full bg-brand-50 text-brand-700 mx-auto mb-3">
            <?= icon('clock', 'w-6 h-6') ?>
        </span>
        <h1 class="text-xl font-bold text-slate-900">Recuperar contraseña</h1>
        <p class="text-sm text-slate-500 mt-1">
            Escribe el correo de tu cuenta y te enviaremos las instrucciones para crear una nueva contraseña.
        </p>
    </div>

    <form method="post" action="<?= e(url('auth/recuperar')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>

        <div>
            <label for="email" class="input-label">Correo electrónico</label>
            <input type="email" id="email" name="email" required autofocus
                   value="<?= e((string) old('email')) ?>"
                   placeholder="usuario@clinica.test"
                   autocomplete="email"
                   aria-invalid="<?= !empty($errors['email']) ? 'true' : 'false' ?>"
                   class="input <?= !empty($errors['email']) ? 'border-rose-400' : '' ?>">
            <?php if (!empty($errors['email'])): ?>
                <p class="mt-1 text-xs text-rose-600"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit"
                class="w-full bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg
                       px-4 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-brand-500">
            Enviar instrucciones
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="<?= e(url('auth/login')) ?>" class="font-semibold text-brand-700 hover:underline">
            ← Volver a iniciar sesión
        </a>
    </p>
</div>
