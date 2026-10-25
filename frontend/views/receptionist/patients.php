<?php
/**
 * Listado de pacientes (recepción / administración).
 * Variables del controlador (PacienteController::index):
 *  $patients   array filas de v_patients
 *              (id, user_id, full_name, first_name, last_name, document_type, document_number,
 *               birth_date, gender, blood_type, address, emergency_contact_name,
 *               emergency_contact_phone, insurance, allergies, base_diseases,
 *               medical_background, email, phone, active)
 *  $filters    array (q)
 *  $pagination array resultado de paginate()
 *  $editing    ?array paciente resaltado en el listado (acción "editar")
 *  $errors     array errores de validación por campo
 */

$patients   = $patients ?? [];
$search     = (string) ($filters['q'] ?? '');
$filters    = ['q' => $search];
$pagination = $pagination ?? null;
$editing    = $editing ?? null;
$errors     = $errors ?? [];
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Pacientes registrados</h2>
        <p class="text-sm text-slate-500">Busca por nombre, documento o correo electrónico.</p>
    </div>
    <a href="<?= e(url('pacientes/crear')) ?>"
       class="inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg
              bg-brand-700 hover:bg-brand-800 text-white transition
              focus:outline-none focus:ring-2 focus:ring-brand-500">
        <?= icon('plus', 'w-4 h-4') ?>
        Nuevo paciente
    </a>
</div>

<?php if ($errors): ?>
    <div class="mb-4 border-l-4 border-rose-400 bg-rose-50 text-rose-800 rounded-r-lg px-4 py-3 text-sm">
        <?php foreach ($errors as $error): ?>
            <p><?= e($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Buscador -->
<div class="card p-4 mb-4">
    <form method="get" action="<?= e(url('pacientes/index')) ?>" class="flex flex-col sm:flex-row gap-2">
        <input type="hidden" name="r" value="pacientes/index">
        <div class="flex-1">
            <label for="q" class="sr-only">Buscar paciente</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <?= icon('search', 'w-4 h-4') ?>
                </span>
                <input type="text" name="q" id="q" value="<?= e($filters['q']) ?>"
                       placeholder="Nombre, documento o correo..."
                       class="input pl-9" autocomplete="off">
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm
                           font-semibold rounded-lg bg-brand-700 hover:bg-brand-800 text-white transition
                           focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?= icon('search', 'w-4 h-4') ?> Buscar
            </button>
            <?php if ($filters['q'] !== ''): ?>
                <a href="<?= e(url('pacientes/index')) ?>"
                   class="flex-1 sm:flex-none inline-flex justify-center items-center px-5 py-2.5 text-sm
                          font-semibold rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                    Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Listado -->
<div class="card p-5">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <?= icon('users', 'w-5 h-5 text-brand-700') ?>
            <h2 class="text-sm font-bold text-slate-900">
                Resultados
                <span class="ml-1 text-slate-400 font-normal">(<?= (int) ($pagination['total'] ?? count($patients)) ?>)</span>
            </h2>
        </div>
        <?php if ($editing): ?>
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 text-brand-800 px-3 py-1 text-xs font-semibold">
                Editando: <?= e($editing['full_name'] ?? '') ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if (!$patients): ?>
        <div class="py-10 text-center">
            <span class="grid place-items-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto mb-3">
                <?= icon('user', 'w-6 h-6') ?>
            </span>
            <p class="text-sm font-semibold text-slate-700">
                <?= $filters['q'] !== '' ? 'No se encontraron pacientes con ese criterio.' : 'Aún no hay pacientes registrados.' ?>
            </p>
            <a href="<?= e(url('pacientes/crear')) ?>"
               class="mt-3 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg
                      bg-brand-700 hover:bg-brand-800 text-white transition">
                <?= icon('plus', 'w-4 h-4') ?> Registrar paciente
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                        <th class="py-2 pr-4 font-semibold">Paciente</th>
                        <th class="py-2 pr-4 font-semibold">Documento</th>
                        <th class="py-2 pr-4 font-semibold">Teléfono / Correo</th>
                        <th class="py-2 pr-4 font-semibold">Nacimiento</th>
                        <th class="py-2 pr-4 font-semibold">Sangre</th>
                        <th class="py-2 pr-4 font-semibold">Alergias</th>
                        <th class="py-2 font-semibold text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($patients as $p): ?>
                        <?php $isEditing = $editing && (int) $editing['id'] === (int) $p['id']; ?>
                        <tr class="hover:bg-slate-50 align-top <?= $isEditing ? 'bg-brand-50' : '' ?>">
                            <td class="py-2.5 pr-4">
                                <span class="block font-semibold text-slate-800"><?= e($p['full_name']) ?></span>
                            </td>
                            <td class="py-2.5 pr-4 text-slate-700 whitespace-nowrap">
                                <span class="block"><?= e($p['document_number']) ?></span>
                                <span class="block text-xs text-slate-500"><?= e($p['document_type'] ?: 'DNI') ?></span>
                            </td>
                            <td class="py-2.5 pr-4">
                                <span class="block text-slate-700 whitespace-nowrap"><?= e($p['phone'] ?: '—') ?></span>
                                <span class="block text-xs text-slate-500 truncate max-w-[14rem]"><?= e($p['email']) ?></span>
                            </td>
                            <td class="py-2.5 pr-4 text-slate-700 whitespace-nowrap">
                                <?= e(format_date($p['birth_date'])) ?>
                            </td>
                            <td class="py-2.5 pr-4">
                                <?php if (!empty($p['blood_type'])): ?>
                                    <span class="inline-flex items-center rounded-full bg-rose-50 text-rose-700 px-2.5 py-1 text-xs font-semibold">
                                        <?= e($p['blood_type']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 pr-4 text-slate-600 max-w-[16rem]">
                                <?php if (!empty($p['allergies'])): ?>
                                    <span class="inline-flex items-start gap-1">
                                        <?= icon('heart', 'w-4 h-4 text-amber-500 mt-0.5 shrink-0') ?>
                                        <span class="line-clamp-2"><?= e($p['allergies']) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400">Ninguna registrada</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 text-right whitespace-nowrap">
                                <a href="<?= e(url('pacientes/editar', ['id' => $p['id']])) ?>"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200
                                          text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                    <?= icon('edit', 'w-4 h-4') ?> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $extra = ['q' => $filters['q']]; ?>
<?php include FRONTEND_PATH . '/views/shared/pagination.php'; ?>
