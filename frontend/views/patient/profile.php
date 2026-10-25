<?php
/**
 * Perfil personal del paciente (datos editables + información clínica de solo lectura).
 * Variables del controlador (PacienteController::perfil):
 *  $patient array fila v_patients (id, user_id, first_name, last_name, email, phone,
 *            document_type, document_number, birth_date, gender, blood_type, address,
 *            emergency_contact_name, emergency_contact_phone, insurance, allergies,
 *            base_diseases, medical_background)
 *  $errors  array errores de validación por campo
 */

$patient = $patient ?? [];
$errors  = $errors ?? [];

/** Prioriza el valor repoblado (old) y, si no existe, el guardado en BD. */
$val = static function (string $field, mixed $default = '') use ($patient): mixed {
    $old = old($field);
    return ($old !== '' && $old !== null) ? $old : ($patient[$field] ?? $default);
};

$genderLabels = ['F' => 'Femenino', 'M' => 'Masculino', 'O' => 'Otro'];
$gender       = (string) $val('gender', (string) ($patient['gender'] ?? ''));
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Mi perfil</h2>
        <p class="text-sm text-slate-500">
            Actualiza tus datos de contacto, seguridad y revisa tu información clínica.
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
        <form method="post" action="<?= e(url('pacientes/guardarPerfil')) ?>"
              id="profile-form" class="space-y-5" novalidate>
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
                               aria-invalid="<?= !empty($errors['phone']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['phone'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['phone']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="document_number" class="input-label">N° de documento *</label>
                        <input type="text" id="document_number" name="document_number" required maxlength="30"
                               value="<?= e((string) $val('document_number')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['document_number']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['document_number'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['document_number']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="birth_date" class="input-label">Fecha de nacimiento</label>
                        <input type="date" id="birth_date" name="birth_date" max="<?= e(date('Y-m-d')) ?>"
                               value="<?= e((string) $val('birth_date')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['birth_date']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['birth_date'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['birth_date']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="gender" class="input-label">Sexo</label>
                        <select id="gender" name="gender" class="input">
                            <option value="">— Selecciona —</option>
                            <?php foreach ($genderLabels as $g => $gLabel): ?>
                                <option value="<?= e($g) ?>" <?= $gender === $g ? 'selected' : '' ?>>
                                    <?= e($gLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <?= icon('users', 'w-5 h-5 text-brand-700') ?>
                    <h3 class="text-sm font-bold text-slate-900">Contacto</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="address" class="input-label">Dirección</label>
                        <textarea name="address" id="address" rows="2" maxlength="255"
                                  placeholder="Calle, número, ciudad..."
                                  class="input"><?= e((string) $val('address')) ?></textarea>
                        <?php if (!empty($errors['address'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['address']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="emergency_contact_name" class="input-label">Contacto de emergencia</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" maxlength="120"
                               placeholder="Nombre y parentesco"
                               value="<?= e((string) $val('emergency_contact_name')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['emergency_contact_name']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['emergency_contact_name'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['emergency_contact_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="emergency_contact_phone" class="input-label">Teléfono de emergencia</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" maxlength="30"
                               value="<?= e((string) $val('emergency_contact_phone')) ?>" class="input"
                               aria-invalid="<?= !empty($errors['emergency_contact_phone']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['emergency_contact_phone'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['emergency_contact_phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seguridad -->
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <?= icon('check', 'w-5 h-5 text-brand-700') ?>
                    <h3 class="text-sm font-bold text-slate-900">Seguridad</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="input-label">Nueva contraseña</label>
                        <input type="password" id="password" name="password" minlength="8" maxlength="255"
                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                               title="<?= e(password_rules()) ?>"
                               autocomplete="new-password" placeholder="••••••••" class="input"
                               aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>">
                        <p class="mt-1 text-xs text-slate-500">
                            Déjalo en blanco para mantener tu contraseña actual.<br>
                            <span class="text-slate-400"><?= e(password_rules()) ?></span>
                        </p>
                        <?php if (!empty($errors['password'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="password_confirm" class="input-label">Confirmar contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" minlength="8"
                               maxlength="255" autocomplete="new-password" placeholder="••••••••" class="input">
                        <p id="password_confirm_error" class="mt-1 text-xs text-rose-600 hidden">
                            Las contraseñas no coinciden.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="<?= e(url('dashboard/index')) ?>"
                   class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-semibold rounded-lg
                          border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                    Volver
                </a>
                <button type="submit"
                        class="inline-flex justify-center items-center gap-2 px-6 py-2.5 text-sm font-semibold
                               rounded-lg bg-brand-700 hover:bg-brand-800 text-white transition
                               focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <?= icon('check', 'w-4 h-4') ?>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Información clínica (solo lectura) -->
    <aside class="card p-5 border-l-4 border-brand-600">
        <div class="flex items-center gap-2 mb-3">
            <?= icon('medical', 'w-5 h-5 text-brand-700') ?>
            <h3 class="text-sm font-bold text-slate-900">Información clínica</h3>
        </div>

        <dl class="space-y-3 text-sm">
            <div class="flex items-start justify-between gap-3">
                <dt class="text-xs font-semibold uppercase text-slate-400">Tipo de sangre</dt>
                <dd>
                    <?php if (!empty($patient['blood_type'])): ?>
                        <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-800
                                       px-3 py-1 text-xs font-bold"><?= e($patient['blood_type']) ?></span>
                    <?php else: ?>
                        <span class="text-slate-500">No registrado</span>
                    <?php endif; ?>
                </dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Alergias</dt>
                <dd class="mt-0.5 text-slate-700 whitespace-pre-line">
                    <?php $allergies = trim((string) ($patient['allergies'] ?? '')); ?>
                    <?= $allergies !== '' ? e($allergies) : '<span class="text-slate-500">Sin registros</span>' ?>
                </dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Enfermedades de base</dt>
                <dd class="mt-0.5 text-slate-700 whitespace-pre-line">
                    <?php $baseDiseases = trim((string) ($patient['base_diseases'] ?? '')); ?>
                    <?= $baseDiseases !== '' ? e($baseDiseases) : '<span class="text-slate-500">Sin registros</span>' ?>
                </dd>
            </div>

            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Antecedentes médicos</dt>
                <dd class="mt-0.5 text-slate-700 whitespace-pre-line">
                    <?php $background = trim((string) ($patient['medical_background'] ?? '')); ?>
                    <?= $background !== '' ? e($background) : '<span class="text-slate-500">Sin registros</span>' ?>
                </dd>
            </div>
        </dl>

        <p class="mt-4 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-500">
            Esta información la actualiza el personal de la clínica. Si necesitas corregirla, repórtalo en recepción.
        </p>
    </aside>
</div>

<script>
(function () {
    var form = document.getElementById('profile-form');
    if (!form) return;

    var pass = document.getElementById('password');
    var confirmField = document.getElementById('password_confirm');
    var message = document.getElementById('password_confirm_error');

    form.addEventListener('submit', function (event) {
        if (!pass || !confirmField) return;
        if (pass.value !== '' && pass.value !== confirmField.value) {
            event.preventDefault();
            message.classList.remove('hidden');
            confirmField.focus();
        } else if (message) {
            message.classList.add('hidden');
        }
    });
})();
</script>
