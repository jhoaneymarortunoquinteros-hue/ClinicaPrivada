<?php
/**
 * Formulario de cancelación con motivo (inline).
 * Variables: $appointment_id (int), $extra (array de params para volver)
 */
$appointment_id = (int) ($appointment_id ?? 0);
$extra = $extra ?? [];
?>
<details class="group relative inline-block text-left align-middle">
    <summary class="list-none cursor-pointer select-none inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                    border border-rose-200 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
        <?= icon('x', 'w-4 h-4') ?> Cancelar
    </summary>

    <div class="absolute right-0 z-30 mt-2 w-72 card p-4 shadow-lg">
        <p class="text-sm font-semibold text-slate-800 mb-2">Motivo de la cancelación *</p>
        <form method="post" action="<?= e(url('citas/cancelar', $extra)) ?>" class="space-y-2">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e((string) $appointment_id) ?>">
            <textarea name="reason" rows="2" required maxlength="300"
                      placeholder="Indica el motivo..."
                      class="input text-sm"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:bg-slate-100"
                        onclick="this.closest('details').open = false">
                    Volver
                </button>
                <button type="submit" data-confirm="¿Confirmar la cancelación de esta cita?"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</details>
