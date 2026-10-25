<?php
/**
 * Formulario de alta / edición de paciente (recepción y administración).
 * Variables del controlador (PacienteController::crear / editar):
 *  $title   string "Nuevo paciente" | "Editar paciente"
 *  $patient ?array fila de v_patients al editar (null al crear)
 *            (id, user_id, full_name, first_name, last_name, document_type, document_number,
 *             birth_date, gender, blood_type, address, emergency_contact_name,
 *             emergency_contact_phone, insurance, allergies, base_diseases,
 *             medical_background, email, phone)
 *  $user    ?array fila de users asociada al paciente (null al crear)
 *  $errors  array errores de validación por campo
 */

$errors   = $errors ?? [];
$patient  = $patient ?? null;
$user     = $user ?? null;
$isCreate = $patient === null;

/** Valor repoblado: POST fallido > paciente en edición > usuario > vacío. */
$field = static function (string $name) use ($patient, $user): mixed {
    $fallback = $patient[$name] ?? ($user[$name] ?? '');
    return old($name, $fallback);
};

$documentTypes = ['DNI', 'CC', 'Pasaporte', 'Cédula'];
$genders       = ['F' => 'Femenino', 'M' => 'Masculino', 'O' => 'Otro'];
$bloodTypes    = ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
?>
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-lg font-bold text-slate-900"><?= e($title) ?></h2>
            <p class="text-sm text-slate-500">
                <?= $isCreate
                    ? 'Registra los datos de acceso, identificación e historial del paciente.'
                    : 'Actualiza los datos del paciente y su ficha clínica inicial.' ?>
            </p>
        </div>
        <a href="<?= e(url('pacientes/index')) ?>"
           class="text-sm font-semibold text-slate-600 hover:text-brand-700">← Volver</a>
    </div>

    <?php if ($errors): ?>
        <div class="mb-4 border-l-4 border-rose-400 bg-rose-50 text-rose-800 rounded-r-lg px-4 py-3 text-sm">
            <p class="font-semibold">Revisa los datos marcados antes de continuar.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('pacientes/guardar')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>
        <?php if (!$isCreate): ?>
            <input type="hidden" name="id" value="<?= e((string) $patient['id']) ?>">
        <?php endif; ?>

        <!-- Acceso -->
        <div class="card">
            <div class="flex items-center gap-2 px-5 pt-5 pb-1">
                <?= icon('user', 'w-5 h-5 text-brand-700') ?>
                <h3 class="text-sm font-bold text-slate-900">Acceso</h3>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="input-label">Nombre *</label>
                    <input type="text" name="first_name" id="first_name" required maxlength="80"
                           autocomplete="given-name"
                           value="<?= e((string) $field('first_name')) ?>"
                           aria-invalid="<?= !empty($errors['first_name']) ? 'true' : 'false' ?>"
                           class="input <?= !empty($errors['first_name']) ? 'border-rose-400' : '' ?>">
                    <?php if (!empty($errors['first_name'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['first_name']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="last_name" class="input-label">Apellidos *</label>
                    <input type="text" name="last_name" id="last_name" required maxlength="80"
                           autocomplete="family-name"
                           value="<?= e((string) $field('last_name')) ?>"
                           aria-invalid="<?= !empty($errors['last_name']) ? 'true' : 'false' ?>"
                           class="input <?= !empty($errors['last_name']) ? 'border-rose-400' : '' ?>">
                    <?php if (!empty($errors['last_name'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['last_name']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="email" class="input-label">Correo electrónico *</label>
                    <input type="email" name="email" id="email" required maxlength="120"
                           autocomplete="username"
                           value="<?= e((string) $field('email')) ?>"
                           placeholder="paciente@correo.test"
                           aria-invalid="<?= !empty($errors['email']) ? 'true' : 'false' ?>"
                           class="input <?= !empty($errors['email']) ? 'border-rose-400' : '' ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="phone" class="input-label">Teléfono</label>
                    <input type="tel" name="phone" id="phone" maxlength="30"
                           autocomplete="tel"
                           value="<?= e((string) $field('phone')) ?>"
                           placeholder="Ej.: 300 123 4567"
                           class="input">
                </div>

                <div class="sm:col-span-2">
                    <label for="password" class="input-label">
                        Contraseña<?= $isCreate ? ' *' : '' ?>
                    </label>
                    <input type="password" name="password" id="password"
                           <?= $isCreate ? 'required' : '' ?> minlength="8"
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                           title="<?= e(password_rules()) ?>"
                           autocomplete="new-password" placeholder="••••••••"
                           aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>"
                           class="input sm:max-w-sm <?= !empty($errors['password']) ? 'border-rose-400' : '' ?>">
                    <p class="mt-1 text-xs text-slate-500">
                        <?= e(password_rules()) ?> Déjalo en blanco para conservar la actual.
                    </p>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Identificación -->
        <div class="card">
            <div class="flex items-center gap-2 px-5 pt-5 pb-1">
                <?= icon('file', 'w-5 h-5 text-brand-700') ?>
                <h3 class="text-sm font-bold text-slate-900">Identificación</h3>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="document_type" class="input-label">Tipo de documento</label>
                    <select name="document_type" id="document_type" class="input">
                        <?php foreach ($documentTypes as $type): ?>
                            <option value="<?= e($type) ?>"
                                <?= (string) $field('document_type') === $type ? 'selected' : '' ?>>
                                <?= e($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="document_number" class="input-label">Número de documento *</label>
                    <input type="text" name="document_number" id="document_number" required maxlength="30"
                           value="<?= e((string) $field('document_number')) ?>"
                           aria-invalid="<?= !empty($errors['document_number']) ? 'true' : 'false' ?>"
                           class="input <?= !empty($errors['document_number']) ? 'border-rose-400' : '' ?>">
                    <?php if (!empty($errors['document_number'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['document_number']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="birth_date" class="input-label">Fecha de nacimiento</label>
                    <input type="date" name="birth_date" id="birth_date" max="<?= e(date('Y-m-d')) ?>"
                           value="<?= e((string) $field('birth_date')) ?>"
                           aria-invalid="<?= !empty($errors['birth_date']) ? 'true' : 'false' ?>"
                           class="input <?= !empty($errors['birth_date']) ? 'border-rose-400' : '' ?>">
                    <?php if (!empty($errors['birth_date'])): ?>
                        <p class="mt-1 text-xs text-rose-600"><?= e($errors['birth_date']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="gender" class="input-label">Género</label>
                    <select name="gender" id="gender" class="input">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($genders as $code => $label): ?>
                            <option value="<?= e($code) ?>"
                                <?= (string) $field('gender') === $code ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="blood_type" class="input-label">Tipo de sangre</label>
                    <select name="blood_type" id="blood_type" class="input">
                        <?php foreach ($bloodTypes as $type): ?>
                            <option value="<?= e($type) ?>"
                                <?= (string) $field('blood_type') === $type ? 'selected' : '' ?>>
                                <?= $type === '' ? '— Sin especificar —' : e($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Contacto y seguro -->
        <div class="card">
            <div class="flex items-center gap-2 px-5 pt-5 pb-1">
                <?= icon('heart', 'w-5 h-5 text-brand-700') ?>
                <h3 class="text-sm font-bold text-slate-900">Contacto y seguro</h3>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="address" class="input-label">Dirección</label>
                    <input type="text" name="address" id="address" maxlength="180"
                           autocomplete="street-address"
                           value="<?= e((string) $field('address')) ?>"
                           placeholder="Calle, número, barrio, ciudad"
                           class="input">
                </div>

                <div>
                    <label for="emergency_contact_name" class="input-label">Contacto de emergencia</label>
                    <input type="text" name="emergency_contact_name" id="emergency_contact_name" maxlength="120"
                           value="<?= e((string) $field('emergency_contact_name')) ?>"
                           placeholder="Nombre de la persona a contactar"
                           class="input">
                </div>

                <div>
                    <label for="emergency_contact_phone" class="input-label">Teléfono de emergencia</label>
                    <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" maxlength="30"
                           value="<?= e((string) $field('emergency_contact_phone')) ?>"
                           placeholder="Ej.: 300 123 4567"
                           class="input">
                </div>

                <div class="sm:col-span-2">
                    <label for="insurance" class="input-label">Aseguradora / EPS</label>
                    <input type="text" name="insurance" id="insurance" maxlength="120"
                           value="<?= e((string) $field('insurance')) ?>"
                           placeholder="Nombre de la entidad de salud"
                           class="input">
                </div>
            </div>
        </div>

        <!-- Historial clínico digital -->
        <div class="card">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 pt-5 pb-1">
                <div class="flex items-center gap-2">
                    <?= icon('medical', 'w-5 h-5 text-brand-700') ?>
                    <h3 class="text-sm font-bold text-slate-900">Historial clínico digital</h3>
                </div>
                <p class="text-xs text-slate-500">
                    Lo completa recepción; el médico añade la evolución.
                </p>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label for="allergies" class="input-label">Alergias</label>
                    <textarea name="allergies" id="allergies" rows="2" maxlength="500"
                              placeholder="Ej.: Penicilina, mariscos, yodo..."
                              class="input"><?= e((string) $field('allergies')) ?></textarea>
                </div>

                <div>
                    <label for="base_diseases" class="input-label">Enfermedades de base</label>
                    <textarea name="base_diseases" id="base_diseases" rows="2" maxlength="500"
                              placeholder="Ej.: Hipertensión arterial, diabetes tipo 2..."
                              class="input"><?= e((string) $field('base_diseases')) ?></textarea>
                </div>

                <div>
                    <label for="medical_background" class="input-label">Antecedentes médicos</label>
                    <textarea name="medical_background" id="medical_background" rows="3" maxlength="1000"
                              placeholder="Cirugías, hospitalizaciones, medicación actual..."
                              class="input"><?= e((string) $field('medical_background')) ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a href="<?= e(url('pacientes/index')) ?>"
               class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-semibold rounded-lg
                      border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex justify-center items-center gap-2 px-6 py-2.5 text-sm font-semibold
                           rounded-lg bg-brand-700 hover:bg-brand-800 text-white transition
                           focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?= icon('check', 'w-4 h-4') ?>
                <?= $isCreate ? 'Registrar paciente' : 'Guardar cambios' ?>
            </button>
        </div>
    </form>
</div>
