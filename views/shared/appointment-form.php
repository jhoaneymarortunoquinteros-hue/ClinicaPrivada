<?php
/**
 * Formulario compartido para programar / reprogramar citas.
 * Usado por: Recepcionista, Administrador y Paciente.
 *
 * Variables del controlador:
 *  $mode            'crear' | 'reprogramar'
 *  $appointment     ?array  (cita existente al reprogramar)
 *  $doctors         array   opciones de médicos (id, full_name, specialty_name, duration_minutes)
 *  $patients        array   opciones de pacientes (id, full_name, document_number)
 *  $patient         ?array  paciente preseleccionado
 *  $canChoosePatient bool
 *  $selectedPatient int
 *  $selectedDoctor  int
 *  $date            string  YYYY-MM-DD
 *  $slots           array   franjas libres [{start,end,label}]
 *  $errors          array   errores de validación por campo
 */

$errors = $errors ?? [];
$isReprogram = ($mode ?? 'crear') === 'reprogramar';

$selPatient = (int) (old('patient_id') ?: ($selectedPatient ?? 0));
$selDoctor = (int) (old('doctor_id') ?: ($selectedDoctor ?? 0));
$selDate = (string) (old('scheduled_date') !== '' ? old('scheduled_date') : ($date ?? date('Y-m-d')));
$selStart = (string) old('start_time');

$err = static function (string $field) use ($errors): ?string {
    return $errors[$field] ?? null;
};
?>
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-lg font-bold text-slate-900"><?= e($title ?? 'Programar cita') ?></h2>
            <p class="text-sm text-slate-500">
                <?= $isReprogram
                    ? 'Selecciona la nueva fecha y hora de la cita.'
                    : 'Completa los datos para agendar la consulta.' ?>
            </p>
        </div>
        <a href="<?= e(url('citas/index')) ?>"
           class="text-sm font-semibold text-slate-600 hover:text-brand-700">← Volver</a>
    </div>

    <form method="post"
          action="<?= e(url($isReprogram ? 'citas/guardarReprogramacion' : 'citas/guardar')) ?>"
          class="card p-5 sm:p-6 space-y-5" id="appointment-form" novalidate>
        <?= csrf_field() ?>
        <?php if ($isReprogram && $appointment): ?>
            <input type="hidden" name="id" value="<?= e((string) $appointment['id']) ?>">
        <?php endif; ?>

        <?php if (isset($errors['start_time'])): ?>
            <div class="border-l-4 border-rose-400 bg-rose-50 text-rose-800 rounded-r-lg px-4 py-3 text-sm">
                <?= e($errors['start_time']) ?>
            </div>
        <?php endif; ?>

        <!-- Paciente -->
        <?php if ($canChoosePatient): ?>
            <div>
                <label for="patient_id" class="input-label">Paciente *</label>
                <select name="patient_id" id="patient_id" required class="input"
                        aria-invalid="<?= $err('patient_id') ? 'true' : 'false' ?>">
                    <option value="">— Selecciona un paciente —</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= e((string) $p['id']) ?>"
                            <?= $selPatient === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['full_name']) ?> · <?= e($p['document_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($err('patient_id')): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($err('patient_id')) ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3">
                <p class="input-label">Paciente</p>
                <p class="text-sm font-semibold text-slate-800">
                    <?= e($patient['full_name'] ?? '—') ?>
                    <span class="font-normal text-slate-500">· Doc. <?= e($patient['document_number'] ?? '—') ?></span>
                </p>
                <input type="hidden" name="patient_id" value="<?= e((string) ($patient['id'] ?? $selPatient)) ?>">
            </div>
            <?php if ($err('patient_id')): ?>
                <p class="mt-1 text-xs text-rose-600"><?= e($err('patient_id')) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Médico + fecha -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="doctor_id" class="input-label">Médico *</label>
                <?php if ($isReprogram): ?>
                    <select name="doctor_id" id="doctor_id" class="input bg-slate-100" disabled>
                        <?php foreach ($doctors as $d): ?>
                            <?php if ((int) $d['id'] === $selDoctor): ?>
                                <option value="<?= e((string) $d['id']) ?>" selected>
                                    <?= e($d['full_name']) ?> · <?= e($d['specialty_name']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="doctor_id" value="<?= e((string) $selDoctor) ?>">
                <?php else: ?>
                    <select name="doctor_id" id="doctor_id" required class="input"
                            aria-invalid="<?= $err('doctor_id') ? 'true' : 'false' ?>">
                        <option value="">— Selecciona un médico —</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= e((string) $d['id']) ?>"
                                    data-duration="<?= e((string) $d['duration_minutes']) ?>"
                                    data-specialty="<?= e($d['specialty_name']) ?>"
                                    <?= $selDoctor === (int) $d['id'] ? 'selected' : '' ?>>
                                <?= e($d['full_name']) ?> · <?= e($d['specialty_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?php if ($err('doctor_id')): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($err('doctor_id')) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="scheduled_date" class="input-label">Fecha *</label>
                <input type="date" name="scheduled_date" id="scheduled_date" required
                       min="<?= $isReprogram ? '' : e(date('Y-m-d')) ?>"
                       value="<?= e($selDate) ?>" class="input"
                       aria-invalid="<?= $err('scheduled_date') ? 'true' : 'false' ?>">
                <?php if ($err('scheduled_date')): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($err('scheduled_date')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Horario disponible -->
        <div class="rounded-lg border border-brand-100 bg-brand-50 px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <p class="text-sm font-semibold text-brand-900">Horario disponible</p>
                <p class="text-xs text-brand-800" id="duration-hint">
                    <?php
                    $currentDoctor = null;
                    foreach ($doctors as $d) {
                        if ((int) $d['id'] === $selDoctor) {
                            $currentDoctor = $d;
                            break;
                        }
                    }
                    ?>
                    <?php if ($currentDoctor): ?>
                        Duración de la consulta: <strong><?= e((string) $currentDoctor['duration_minutes']) ?> min</strong>
                        (<?= e($currentDoctor['specialty_name']) ?>)
                    <?php else: ?>
                        Selecciona un médico para ver sus franjas libres.
                    <?php endif; ?>
                </p>
            </div>

            <label for="start_time" class="input-label">Hora de inicio *</label>
            <select name="start_time" id="start_time" required class="input"
                    aria-invalid="<?= $err('start_time') ? 'true' : 'false' ?>">
                <?php if (!$slots): ?>
                    <option value="">Sin disponibilidad para esta fecha</option>
                <?php else: ?>
                    <option value="">— Selecciona una hora —</option>
                    <?php foreach ($slots as $slot): ?>
                        <option value="<?= e($slot['start']) ?>" <?= $selStart === $slot['start'] ? 'selected' : '' ?>>
                            <?= e($slot['label']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="mt-1 text-xs text-slate-500">
                Las franjas se calculan según el horario laboral del médico y las citas ya ocupadas.
            </p>
        </div>

        <!-- Motivo / notas -->
        <div>
            <label for="reason" class="input-label">Motivo de la cita<?= $isReprogram ? '' : ' *' ?></label>
            <input type="text" name="reason" id="reason" maxlength="180"
                   placeholder="Ej.: Control de presión arterial"
                   value="<?= e((string) (old('reason', $appointment['reason'] ?? ''))) ?>"
                   class="input">
        </div>

        <div>
            <label for="notes" class="input-label">Notas adicionales</label>
            <textarea name="notes" id="notes" rows="2" maxlength="500"
                      placeholder="Información relevante para la atención"
                      class="input"><?= e((string) old('notes', $appointment['notes'] ?? '')) ?></textarea>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a href="<?= e(url('citas/index')) ?>"
               class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-semibold rounded-lg
                      border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex justify-center items-center gap-2 px-6 py-2.5 text-sm font-semibold
                           rounded-lg bg-brand-700 hover:bg-brand-800 text-white transition
                           focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?= icon('check', 'w-4 h-4') ?>
                <?= $isReprogram ? 'Guardar cambios' : 'Programar cita' ?>
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var doctorSelect = document.getElementById('doctor_id');
    var dateInput = document.getElementById('scheduled_date');
    var slotSelect = document.getElementById('start_time');
    var durationHint = document.getElementById('duration-hint');
    var endpoint = '<?= e(url('citas/disponibilidad')) ?>';
    var initialStart = <?= json_encode($selStart) ?>;
    var isReprogram = <?= json_encode($isReprogram) ?>;

    function renderSlots(data) {
        var slots = data.slots || [];
        slotSelect.innerHTML = '';

        if (data.duration && durationHint) {
            durationHint.innerHTML = 'Duración de la consulta: <strong>' + data.duration + ' min</strong>';
        }

        if (!slots.length) {
            var none = document.createElement('option');
            none.value = '';
            none.textContent = 'Sin disponibilidad para esta fecha';
            slotSelect.appendChild(none);
            return;
        }

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Selecciona una hora —';
        slotSelect.appendChild(placeholder);

        slots.forEach(function (slot) {
            var opt = document.createElement('option');
            opt.value = slot.start;
            opt.textContent = slot.label;
            if (initialStart && slot.start === initialStart) {
                opt.selected = true;
                initialStart = '';
            }
            slotSelect.appendChild(opt);
        });
    }

    function loadSlots() {
        if (isReprogram && !dateInput.value) return;
        var doctorId = doctorSelect ? doctorSelect.value : '';
        if (!doctorId || !dateInput.value) return;

        slotSelect.disabled = true;
        fetch(endpoint + '?doctor_id=' + encodeURIComponent(doctorId) +
              '&date=' + encodeURIComponent(dateInput.value), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                slotSelect.disabled = false;
                renderSlots(data);
            })
            .catch(function () { slotSelect.disabled = false; });
    }

    if (doctorSelect && !isReprogram) doctorSelect.addEventListener('change', loadSlots);
    if (dateInput) dateInput.addEventListener('change', loadSlots);

    // Si el navegador no tiene JS, el select ya viene cargado desde el servidor.
})();
</script>
