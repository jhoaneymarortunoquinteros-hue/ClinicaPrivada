<?php
/**
 * Auto-registro de pacientes (público).
 * Variables: $title, $errors
 */
$errors = $errors ?? [];
$field = static function (string $name, string $default = '') use ($errors): string {
    return e((string) (old($name) !== '' ? old($name) : $default));
};
?>
<div class="card p-6 sm:p-8 shadow-xl">
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-slate-900">Crear cuenta de paciente</h1>
        <p class="text-sm text-slate-500 mt-1">Regístrate para agendar tus citas en línea</p>
    </div>

    <form method="post" action="<?= e(url('auth/register')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="input-label">Nombres *</label>
                <input type="text" id="first_name" name="first_name" required
                       value="<?= $field('first_name') ?>" class="input"
                       aria-invalid="<?= !empty($errors['first_name']) ? 'true' : 'false' ?>">
                <?php if (!empty($errors['first_name'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['first_name']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label for="last_name" class="input-label">Apellidos *</label>
                <input type="text" id="last_name" name="last_name" required
                       value="<?= $field('last_name') ?>" class="input"
                       aria-invalid="<?= !empty($errors['last_name']) ? 'true' : 'false' ?>">
                <?php if (!empty($errors['last_name'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['last_name']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <label for="username" class="input-label">Nombre de usuario *</label>
            <input type="text" id="username" name="username" required maxlength="40"
                   value="<?= $field('username') ?>" placeholder="tu.nombre.de.usuario"
                   autocapitalize="none" spellcheck="false" class="input"
                   aria-invalid="<?= !empty($errors['username']) ? 'true' : 'false' ?>">
            <p class="mt-1 text-xs text-slate-500">Será con lo que inicies sesión. Letras, números, punto, guion o guion bajo.</p>
            <?php if (!empty($errors['username'])): ?>
                <p class="mt-1 text-xs text-rose-600"><?= e($errors['username']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="input-label">Teléfono</label>
                <input type="tel" id="phone" name="phone" value="<?= $field('phone') ?>" class="input">
            </div>
            <div>
                <label for="document_number" class="input-label">N° de documento *</label>
                <input type="text" id="document_number" name="document_number" required
                       value="<?= $field('document_number') ?>" class="input"
                       aria-invalid="<?= !empty($errors['document_number']) ? 'true' : 'false' ?>">
                <?php if (!empty($errors['document_number'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['document_number']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="document_type" class="input-label">Tipo de documento</label>
                <select id="document_type" name="document_type" class="input">
                    <?php foreach (['DNI', 'CC', 'Pasaporte', 'Cédula'] as $type): ?>
                        <option value="<?= e($type) ?>" <?= old('document_type', 'DNI') === $type ? 'selected' : '' ?>>
                            <?= e($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="birth_date" class="input-label">Fecha de nacimiento</label>
                <input type="date" id="birth_date" name="birth_date" max="<?= e(date('Y-m-d')) ?>"
                       value="<?= $field('birth_date') ?>" class="input">
            </div>
            <div>
                <label for="gender" class="input-label">Sexo</label>
                <select id="gender" name="gender" class="input">
                    <option value="">—</option>
                    <?php foreach (['F' => 'Femenino', 'M' => 'Masculino', 'O' => 'Otro'] as $g => $label): ?>
                        <option value="<?= e($g) ?>" <?= old('gender') === $g ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="input-label">Contraseña *</label>
                <input type="password" id="password" name="password" required minlength="8"
                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                       title="Mínimo 8 caracteres, con mayúscula, minúscula, número y un carácter especial."
                       autocomplete="new-password" class="input"
                       aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>">
                <?php if (!empty($errors['password'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label for="confirm" class="input-label">Confirmar contraseña *</label>
                <input type="password" id="confirm" name="confirm" required minlength="8"
                       autocomplete="new-password" class="input"
                       aria-invalid="<?= !empty($errors['confirm']) ? 'true' : 'false' ?>">
                <?php if (!empty($errors['confirm'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['confirm']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <p class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-500">
            <span class="font-semibold text-slate-600">La contraseña debe tener:</span>
            mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un carácter especial.
        </p>

        <button type="submit"
                class="w-full bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg
                       px-4 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-brand-500">
            Crear cuenta
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        ¿Ya tienes cuenta?
        <a href="<?= e(url('auth/login')) ?>" class="font-semibold text-brand-700 hover:underline">Inicia sesión</a>
    </p>
</div>
