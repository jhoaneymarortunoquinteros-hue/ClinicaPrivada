<?php
/**
 * Historial clínico digital de un paciente (vista del médico).
 * Variables: $patient (v_patients), $records (v_medical_records),
 *            $appointment (?v_appointments), $appointmentId (int),
 *            $canEdit (bool), $errors
 */
$errors = $errors ?? [];
$val = static function (string $field, mixed $default = ''): mixed {
    $old = old($field);
    return ($old !== '' && $old !== null) ? $old : $default;
};
?>
<a href="<?= e(url('historial/pacientes')) ?>"
   class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-brand-700 mb-4">
    ← Volver a mis pacientes
</a>

<!-- Ficha del paciente -->
<div class="card p-5 mb-5">
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="grid place-items-center w-14 h-14 rounded-full bg-brand-100 text-brand-800
                         text-lg font-bold shrink-0">
                <?= e(strtoupper(mb_substr($patient['first_name'], 0, 1) . mb_substr($patient['last_name'], 0, 1))) ?>
            </span>
            <div>
                <h2 class="text-lg font-bold text-slate-900"><?= e($patient['full_name']) ?></h2>
                <p class="text-sm text-slate-500">
                    Doc. <?= e($patient['document_number']) ?> ·
                    <?= $patient['gender'] === 'F' ? 'Femenino' : ($patient['gender'] === 'M' ? 'Masculino' : ($patient['gender'] ? 'Otro' : '—')) ?> ·
                    <?= $patient['birth_date'] ? e(format_date($patient['birth_date'])) : 'Sin fecha de nacimiento' ?>
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?= e($patient['phone'] ?: '—') ?> · <?= e($patient['email']) ?>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <?php if ($patient['blood_type']): ?>
                <span class="px-3 py-1.5 rounded-full text-sm font-bold bg-rose-100 text-rose-700">
                    Sangre <?= e($patient['blood_type']) ?>
                </span>
            <?php endif; ?>
            <?php if ($patient['insurance']): ?>
                <span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-sky-100 text-sky-800">
                    <?= e($patient['insurance']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Riesgo clínico -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-5">
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-bold text-rose-700 uppercase mb-1">Alergias</p>
            <p class="text-sm text-rose-800"><?= e($patient['allergies'] ?: 'Ninguna registrada') ?></p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-bold text-amber-700 uppercase mb-1">Enfermedades de base</p>
            <p class="text-sm text-amber-800"><?= e($patient['base_diseases'] ?: 'Ninguna registrada') ?></p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-bold text-slate-600 uppercase mb-1">Antecedentes médicos</p>
            <p class="text-sm text-slate-700"><?= e($patient['medical_background'] ?: 'Sin antecedentes registrados') ?></p>
        </div>
    </div>

    <?php if ($patient['emergency_contact_name']): ?>
        <p class="text-xs text-slate-500 mt-3">
            Contacto de emergencia: <strong><?= e($patient['emergency_contact_name']) ?></strong>
            <?= $patient['emergency_contact_phone'] ? ' · ' . e($patient['emergency_contact_phone']) : '' ?>
        </p>
    <?php endif; ?>

    <?php if ($appointment): ?>
        <div class="mt-4 rounded-lg border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-900">
            <?= icon('calendar', 'w-4 h-4 inline align-text-bottom') ?>
            Cita seleccionada:
            <strong><?= e(format_date($appointment['scheduled_date'])) ?></strong>
            · <?= e(format_time($appointment['start_time'])) ?>–<?= e(format_time($appointment['end_time'])) ?>
            · <?= e($appointment['status']) ?>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
    <!-- Evoluciones -->
    <div class="lg:col-span-3 card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900">Evolución por consulta</h3>
            <p class="text-xs text-slate-500"><?= count($records) ?> registro(s) en el historial</p>
        </div>

        <div class="p-5 space-y-4">
            <?php if (!$records): ?>
                <p class="text-sm text-slate-400 text-center py-6">
                    Sin evoluciones registradas para este paciente.
                </p>
            <?php endif; ?>

            <?php foreach ($records as $r): ?>
                <article class="rounded-xl border border-slate-200 p-4">
                    <header class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <p class="text-sm font-bold text-slate-900">
                            <?= icon('file', 'w-4 h-4 inline align-text-bottom text-brand-700') ?>
                            <?= e(format_date($r['consultation_date'])) ?>
                        </p>
                        <p class="text-xs text-slate-500">
                            <?= e($r['doctor_name']) ?> · <?= e($r['specialty_name']) ?>
                        </p>
                    </header>

                    <dl class="space-y-1.5 text-sm">
                        <?php if (!empty($r['chief_complaint'])): ?>
                            <div class="flex gap-2">
                                <dt class="font-semibold text-slate-600 shrink-0 w-32">Motivo</dt>
                                <dd class="text-slate-700"><?= e($r['chief_complaint']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($r['diagnosis'])): ?>
                            <div class="flex gap-2">
                                <dt class="font-semibold text-slate-600 shrink-0 w-32">Diagnóstico</dt>
                                <dd class="text-slate-800 font-medium"><?= e($r['diagnosis']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($r['treatment'])): ?>
                            <div class="flex gap-2">
                                <dt class="font-semibold text-slate-600 shrink-0 w-32">Tratamiento</dt>
                                <dd class="text-slate-700"><?= e($r['treatment']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($r['evolution'])): ?>
                            <div class="flex gap-2">
                                <dt class="font-semibold text-slate-600 shrink-0 w-32">Evolución</dt>
                                <dd class="text-slate-700"><?= e($r['evolution']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($r['follow_up_date'])): ?>
                            <div class="flex gap-2">
                                <dt class="font-semibold text-slate-600 shrink-0 w-32">Seguimiento</dt>
                                <dd class="text-brand-700 font-semibold"><?= e(format_date($r['follow_up_date'])) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Nueva evolución -->
    <div class="lg:col-span-2">
        <?php if ($canEdit): ?>
            <div class="card p-5 border-l-4 border-brand-600 sticky top-24">
                <h3 class="font-bold text-slate-900 mb-1">Registrar evolución</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Si la cita asociada está pendiente, se marcará como atendida automáticamente.
                </p>

                <form method="post" action="<?= e(url('historial/guardar')) ?>" class="space-y-3" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="patient_id" value="<?= e((string) $patient['id']) ?>">
                    <input type="hidden" name="appointment_id" value="<?= e((string) $appointmentId) ?>">

                    <?php if (!empty($errors['appointment_id'])): ?>
                        <p class="text-xs text-rose-600"><?= e($errors['appointment_id']) ?></p>
                    <?php endif; ?>

                    <div>
                        <label for="consultation_date" class="input-label">Fecha de consulta *</label>
                        <input type="date" id="consultation_date" name="consultation_date" required
                               max="<?= e(date('Y-m-d')) ?>" class="input"
                               value="<?= e((string) $val('consultation_date', date('Y-m-d'))) ?>"
                               aria-invalid="<?= !empty($errors['consultation_date']) ? 'true' : 'false' ?>">
                        <?php if (!empty($errors['consultation_date'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['consultation_date']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="chief_complaint" class="input-label">Motivo de consulta</label>
                        <input type="text" id="chief_complaint" name="chief_complaint" maxlength="200"
                               class="input" value="<?= e((string) $val('chief_complaint')) ?>">
                    </div>

                    <div>
                        <label for="diagnosis" class="input-label">Diagnóstico</label>
                        <textarea id="diagnosis" name="diagnosis" rows="2" class="input"
                                  aria-invalid="<?= !empty($errors['diagnosis']) ? 'true' : 'false' ?>"><?= e((string) $val('diagnosis')) ?></textarea>
                        <?php if (!empty($errors['diagnosis'])): ?>
                            <p class="mt-1 text-xs text-rose-600"><?= e($errors['diagnosis']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="treatment" class="input-label">Tratamiento indicado</label>
                        <textarea id="treatment" name="treatment" rows="2" class="input"><?= e((string) $val('treatment')) ?></textarea>
                    </div>

                    <div>
                        <label for="evolution" class="input-label">Evolución / notas</label>
                        <textarea id="evolution" name="evolution" rows="3" class="input"><?= e((string) $val('evolution')) ?></textarea>
                    </div>

                    <div>
                        <label for="follow_up_date" class="input-label">Próxima consulta</label>
                        <input type="date" id="follow_up_date" name="follow_up_date" class="input"
                               value="<?= e((string) $val('follow_up_date')) ?>">
                    </div>

                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg
                                   bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition">
                        <?= icon('check', 'w-4 h-4') ?> Registrar evolución
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="card p-5 text-sm text-slate-500">
                Solo el médico puede registrar evoluciones médicas.
            </div>
        <?php endif; ?>
    </div>
</div>
