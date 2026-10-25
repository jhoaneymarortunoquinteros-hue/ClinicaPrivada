<?php
/**
 * Inicio de sesión (público).
 * Variables del controlador: $title, $notice, $errors
 */
$errors = $errors ?? [];
?>
<div class="card p-6 sm:p-8 shadow-xl">
    <div class="mb-5 text-center">
        <h1 class="text-xl font-bold text-slate-900">Iniciar sesión</h1>
        <p class="text-sm text-slate-500 mt-1">Ingresa con tu nombre de usuario y contraseña</p>
    </div>

    <!-- CU01: acceso según el rol con el que entras (no muestra credenciales) -->
    <div class="mb-3">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-2">Entrar como</p>
        <div class="grid grid-cols-2 gap-2" id="role-cards">
            <button type="button" class="role-card" data-role="admin"
                    data-hint="Administrador: gestiona usuarios, médicos, especialidades, pacientes, citas y reportes.">
                <span class="role-icon"><?= icon('grid', 'w-4 h-4') ?></span>
                <span>Administrador</span>
            </button>
            <button type="button" class="role-card" data-role="recepcionista"
                    data-hint="Recepcionista: registra pacientes, programa citas y marca la asistencia del día.">
                <span class="role-icon"><?= icon('calendar', 'w-4 h-4') ?></span>
                <span>Recepción</span>
            </button>
            <button type="button" class="role-card" data-role="medico"
                    data-hint="Médico: consulta su agenda, atiende citas y registra la historia clínica.">
                <span class="role-icon"><?= icon('medical', 'w-4 h-4') ?></span>
                <span>Médico</span>
            </button>
            <button type="button" class="role-card" data-role="paciente"
                    data-hint="Paciente: solicita citas, revisa su historial y actualiza su perfil.">
                <span class="role-icon"><?= icon('heart', 'w-4 h-4') ?></span>
                <span>Paciente</span>
            </button>
        </div>
        <p id="role-hint" class="hidden mt-2 rounded-lg px-3 py-2 text-xs"></p>
    </div>

    <?php if (!empty($errors['password'])): ?>
        <div class="mb-4 border-l-4 border-rose-400 bg-rose-50 text-rose-800 rounded-r-lg px-4 py-3 text-sm">
            <?= e($errors['password']) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('auth/login')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>

        <div>
            <label for="username" class="input-label">Nombre de usuario</label>
            <input type="text" id="username" name="username" required autocomplete="username"
                   value="<?= e((string) old('username')) ?>"
                   placeholder="tu.nombre.de.usuario"
                   autocapitalize="none" spellcheck="false"
                   aria-invalid="<?= !empty($errors['username']) ? 'true' : 'false' ?>"
                   class="input <?= !empty($errors['username']) ? 'border-rose-400' : '' ?>">
            <?php if (!empty($errors['username'])): ?>
                <p class="mt-1 text-xs text-rose-600"><?= e($errors['username']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-3">
                <label for="password" class="input-label">Contraseña</label>
                <a href="<?= e(url('auth/recuperar')) ?>"
                   class="text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
            <input type="password" id="password" name="password" required
                   autocomplete="current-password" placeholder="••••••••"
                   class="input">
        </div>

        <button type="submit"
                class="w-full bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg
                       px-4 py-2.5 text-sm transition focus:outline-none focus:ring-2 focus:ring-brand-500">
            Entrar
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        ¿Aún no tienes cuenta?
        <a href="<?= e(url('auth/register')) ?>" class="font-semibold text-brand-700 hover:underline">
            Regístrate como paciente
        </a>
    </p>
</div>
