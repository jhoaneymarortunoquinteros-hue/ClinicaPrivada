<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Usuario;

/**
 * CU22 — Actualizar perfil personal.
 * Accesible desde el menú de navegación para cualquier rol autenticado.
 * Los pacientes usan su vista ampliada (incluye datos clínicos de contacto).
 */
final class PerfilController
{
    public function index(): void
    {
        Auth::requireLogin();

        if (Auth::role() === 'paciente') {
            redirect('pacientes/perfil');
        }

        view('shared/profile', [
            'title'  => 'Mi perfil',
            'user'   => Usuario::find((int) Auth::id()),
            'errors' => errors(),
        ]);
    }

    public function guardar(): void
    {
        Auth::requireLogin();
        if (!is_post()) {
            redirect('perfil/index');
        }
        if (Auth::role() === 'paciente') {
            redirect('pacientes/perfil');
        }

        $current = Usuario::find((int) Auth::id());
        if (!$current) {
            flash('error', 'No se encontró tu cuenta.');
            redirect('dashboard/index');
        }

        $fields = ['first_name', 'last_name', 'phone', 'email'];
        $first = trim((string) input('first_name'));
        $last = trim((string) input('last_name'));
        $phone = trim((string) input('phone'));
        $email = mb_strtolower(trim((string) input('email')), 'UTF-8');
        $password = (string) input('password');
        $confirm = (string) input('confirm');

        $errors = [];

        if ($first === '') {
            $errors['first_name'] = 'Ingresa tu nombre.';
        }
        if ($last === '') {
            $errors['last_name'] = 'Ingresa tus apellidos.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo electrónico válido.';
        } elseif (Usuario::emailExists($email, (int) $current['id'])) {
            $errors['email'] = 'Ese correo ya está en uso por otra cuenta.';
        }

        if ($password !== '') {
            if (($msg = password_error($password)) !== null) {
                $errors['password'] = $msg;
            } elseif ($password !== $confirm) {
                $errors['confirm'] = 'Las contraseñas no coinciden.';
            }
        }

        if ($errors !== []) {
            validation_fail($errors, $fields, 'perfil/index');
        }

        try {
            Usuario::update((int) $current['id'], [
                'role_id'    => (int) $current['role_id'],
                'email'      => $email,
                'username'   => (string) $current['username'],
                'first_name' => $first,
                'last_name'  => $last,
                'phone'      => $phone,
                'active'     => (bool) $current['active'],
            ], $password);
        } catch (\Throwable $e) {
            validation_fail(['email' => db_error_message($e)], $fields, 'perfil/index');
        }

        // El nombre visible de la sesión se refresca al instante.
        $_SESSION['auth']['first_name'] = $first;
        $_SESSION['auth']['last_name'] = $last;
        $_SESSION['auth']['email'] = $email;

        clear_form_state();
        flash('success', 'Perfil actualizado correctamente.');
        redirect('perfil/index');
    }
}
