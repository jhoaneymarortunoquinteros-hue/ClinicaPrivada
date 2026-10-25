<?php
/**
 * Historial clínico digital del paciente.
 * Variables del controlador (HistorialController::miHistorial):
 *  $patient array fila v_patients (id, first_name, full_name, document_type, document_number,
 *            birth_date, gender, blood_type, insurance, allergies, base_diseases,
 *            medical_background, emergency_contact_name, emergency_contact_phone, phone, email)
 *  $records array filas v_medical_records (consultation_date, chief_complaint, diagnosis,
 *            treatment, evolution, follow_up_date, doctor_name, specialty_name)
 */

$patient = $patient ?? [];
$records = $records ?? [];

$genderLabels = ['F' => 'Femenino', 'M' => 'Masculino', 'O' => 'Otro'];
$gender       = (string) ($patient['gender'] ?? '');
$bloodType    = (string) ($patient['blood_type'] ?? '');
$allergies    = trim((string) ($patient['allergies'] ?? ''));
$baseDiseases = trim((string) ($patient['base_diseases'] ?? ''));
$background   = trim((string) ($patient['medical_background'] ?? ''));
?>
<!-- Ficha clínica digital -->
<div class="card p-5 sm:p-6 mb-5">
    <div class="flex items-center gap-2 mb-4">
        <?= icon('file', 'w-5 h-5 text-brand-700') ?>
        <h2 class="text-sm font-bold text-slate-900">Mi ficha clínica digital</h2>
        <span class="ml-auto hidden sm:block text-xs text-slate-400">
            Documento: <?= e(($patient['document_type'] ?? 'DNI') . ' ' . ($patient['document_number'] ?? '')) ?>
        </span>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase text-slate-400">Tipo de sangre</dt>
            <dd class="mt-1">
                <?php if ($bloodType !== ''): ?>
                    <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-800
                                   px-3 py-1 text-sm font-bold"><?= e($bloodType) ?></span>
                <?php else: ?>
                    <span class="text-sm text-slate-500">No registrado</span>
                <?php endif; ?>
            </dd>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase text-slate-400">Sexo</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-800">
                <?= e($genderLabels[$gender] ?? 'No registrado') ?>
            </dd>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase text-slate-400">Fecha de nacimiento</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-800">
                <?= e(format_date($patient['birth_date'] ?? null)) ?>
            </dd>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase text-slate-400">Documento</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-800">
                <?= e(trim(($patient['document_type'] ?? 'DNI') . ' ' . ($patient['document_number'] ?? '')) ?: 'No registrado') ?>
            </dd>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase text-slate-400">Aseguradora</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-800">
                <?= e(($patient['insurance'] ?? '') !== '' ? $patient['insurance'] : 'Particular / sin registrar') ?>
            </dd>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase text-slate-400">Contacto de emergencia</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-800">
                <?php if (!empty($patient['emergency_contact_name'])): ?>
                    <?= e($patient['emergency_contact_name']) ?>
                    <?php if (!empty($patient['emergency_contact_phone'])): ?>
                        <span class="block text-xs font-normal text-slate-500">
                            Tel: <?= e($patient['emergency_contact_phone']) ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-slate-500">No registrado</span>
                <?php endif; ?>
            </dd>
        </div>
    </dl>
</div>

<!-- Alertas clínicas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="card p-5 border-l-4 border-rose-400">
        <div class="flex items-center gap-2 mb-2">
            <?= icon('heart', 'w-5 h-5 text-rose-600') ?>
            <h3 class="text-sm font-bold text-slate-900">Alergias</h3>
        </div>
        <?php if ($allergies !== ''): ?>
            <p class="text-sm text-rose-700 whitespace-pre-line"><?= e($allergies) ?></p>
        <?php else: ?>
            <p class="text-sm text-slate-500">Sin registros</p>
        <?php endif; ?>
    </div>

    <div class="card p-5">
        <div class="flex items-center gap-2 mb-3">
            <?= icon('medical', 'w-5 h-5 text-brand-700') ?>
            <h3 class="text-sm font-bold text-slate-900">Enfermedades de base y antecedentes</h3>
        </div>
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Enfermedades de base</dt>
                <dd class="mt-0.5 text-slate-700 whitespace-pre-line">
                    <?= $baseDiseases !== '' ? e($baseDiseases) : '<span class="text-slate-500">Sin registros</span>' ?>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-400">Antecedentes médicos</dt>
                <dd class="mt-0.5 text-slate-700 whitespace-pre-line">
                    <?= $background !== '' ? e($background) : '<span class="text-slate-500">Sin registros</span>' ?>
                </dd>
            </div>
        </dl>
    </div>
</div>

<!-- Evolución de las consultas -->
<div class="card p-5 sm:p-6">
    <div class="flex items-center gap-2 mb-5">
        <?= icon('chart', 'w-5 h-5 text-brand-700') ?>
        <h2 class="text-sm font-bold text-slate-900">Evolución de mis consultas</h2>
        <span class="ml-auto text-xs text-slate-500"><?= count($records) ?> consulta(s)</span>
    </div>

    <?php if (!$records): ?>
        <div class="py-8 text-center">
            <span class="mx-auto grid place-items-center w-14 h-14 rounded-full bg-slate-100 text-slate-400">
                <?= icon('file', 'w-7 h-7') ?>
            </span>
            <p class="mt-4 text-sm font-semibold text-slate-600">
                Aún no tienes evoluciones médicas.
            </p>
            <p class="mt-1 text-xs text-slate-400 max-w-md mx-auto">
                Cuando atiendas una cita, el médico dejará aquí el diagnóstico, el tratamiento y las indicaciones.
            </p>
            <div class="mt-4">
                <a href="<?= e(url('citas/crear')) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800
                          text-white text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <?= icon('plus', 'w-4 h-4') ?> Solicitar una cita
                </a>
            </div>
        </div>
    <?php else: ?>
        <ol class="relative ml-3 border-l-2 border-slate-200 space-y-8 pb-2 pl-6">
            <?php foreach ($records as $r): ?>
                <li class="relative">
                    <span class="absolute -left-8 top-1.5 block w-3.5 h-3.5 rounded-full bg-brand-600 ring-4 ring-white"></span>

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-2">
                        <span class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-900">
                            <?= icon('calendar', 'w-4 h-4 text-brand-700') ?>
                            <?= e(fecha_larga($r['consultation_date'])) ?>
                        </span>
                        <span class="text-xs text-slate-500">
                            <?= e($r['doctor_name'] ?? '—') ?> · <?= e($r['specialty_name'] ?? '—') ?>
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2.5 text-sm">
                        <?php if (!empty($r['chief_complaint'])): ?>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-400">Motivo de consulta</p>
                                <p class="mt-0.5 text-slate-700 whitespace-pre-line"><?= e($r['chief_complaint']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($r['diagnosis'])): ?>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-400">Diagnóstico</p>
                                <p class="mt-0.5 font-semibold text-slate-900 whitespace-pre-line"><?= e($r['diagnosis']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($r['treatment'])): ?>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-400">Tratamiento</p>
                                <p class="mt-0.5 text-slate-700 whitespace-pre-line"><?= e($r['treatment']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($r['evolution'])): ?>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-400">Evolución</p>
                                <p class="mt-0.5 text-slate-700 whitespace-pre-line"><?= e($r['evolution']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($r['follow_up_date'])): ?>
                            <p class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 text-amber-800
                                        px-3 py-1 text-xs font-semibold">
                                <?= icon('clock', 'w-3.5 h-3.5') ?>
                                Próxima consulta: <?= e(format_date($r['follow_up_date'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="mt-6 text-center">
            <a href="<?= e(url('citas/crear')) ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-700 hover:bg-brand-800
                      text-white text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?= icon('plus', 'w-4 h-4') ?> Solicitar una cita
            </a>
        </div>
    <?php endif; ?>
</div>
