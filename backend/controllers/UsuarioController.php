<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Usuario;

/**
 * Administración de usuarios, roles y cuentas (solo administrador).
 */
final class UsuarioController
{
    public function __construct()
    {
        Auth::requireRole(['admin']);
    }

    public function index(): void
    {
        $filters = [
            'q'    => (string) input('q'),
            'role' => (string) input('role'),
        ];
        $pagination = paginate(
            Usuario::count($filters),
            (int) input('page', 1),
            15
        );

        $action = (string) input('action');

        view('admin/users', [
            'title'      => 'Gestión de usuarios',
            'users'      => Usuario::list($filters, $pagination['per_page'], $pagination['offset']),
            'roles'      => Usuario::roles(),
            'filters'    => $filters,
            'pagination' => $pagination,
            'editing'    => $action === 'editar' ? Usuario::find((int) input('id')) : null,
            'formOpen'   => in_array($action, ['nuevo', 'editar'], true) || !empty($_SESSION['errors']),
            'errors'     => errors(),
        ]);
    }

    public function guardar(): void
    {
        if (!is_post()) {
            redirect('usuarios/index');
        }

        $id = (int) input('id', 0);
        $fields = ['id', 'first_name', 'last_name', 'email', 'phone', 'role_id', 'active'];

        $data = [
            'role_id'    => (int) input('role_id'),
            'email'      => mb_strtolower((string) input('email'), 'UTF-8'),
            'first_name' => (string) input('first_name'),
            'last_name'  => (string) input('last_name'),
            'phone'      => (string) input('phone'),
            'active'     => input('active') === '1' || input('active') === 'on',
        ];
        $password = (string) input('password');

        $errors = [];
        if ($data['role_id'] <= 0) {
            $errors['role_id'] = 'Selecciona un rol.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        } elseif (Usuario::emailExists($data['email'], $id)) {
            $errors['email'] = 'Ese correo ya está en uso por otro usuario.';
        }
        if ($data['first_name'] === '' || $data['last_name'] === '') {
            $errors['first_name'] = 'Nombre y apellidos son obligatorios.';
        }
        if ($password === '') {
            if ($id === 0) {
                $errors['password'] = 'La contraseña es obligatoria.';
            }
        } else {
            $passwordError = password_error($password);
            if ($passwordError !== null) {
                $errors['password'] = $passwordError;
            }
        }

        // Un usuario no debe auto-degradar su propia cuenta desde este formulario.
        if ($id === (int) Auth::id() && (!$data['active'] || $data['role_id'] !== (int) input('current_role_id', $data['role_id']))) {
            $errors['role_id'] = 'No puedes cambiar tu propio rol ni desactivar tu cuenta aquí.';
        }

        if ($errors) {
            validation_fail($errors, $fields, 'usuarios/index', $id > 0 ? ['action' => 'editar', 'id' => (string) $id] : ['action' => 'nuevo']);
        }

        try {
            if ($id > 0) {
                Usuario::update($id, $data, $password !== '' ? $password : null);
                $message = 'Usuario actualizado correctamente.';
            } else {
                $data['password'] = $password;
                Usuario::create($data);
                $message = 'Usuario creado correctamente.';
            }
        } catch (\Throwable $e) {
            validation_fail(['email' => db_error_message($e)], $fields, 'usuarios/index', $id > 0 ? ['action' => 'editar', 'id' => (string) $id] : ['action' => 'nuevo']);
        }

        clear_form_state();
        flash('success', $message);
        redirect('usuarios/index');
    }

    public function toggle(): void
    {
        $id = (int) input('id', 0);
        if ($id <= 0) {
            redirect('usuarios/index');
        }
        if ($id === (int) Auth::id()) {
            flash('error', 'No puedes desactivar tu propia cuenta.');
            redirect('usuarios/index');
        }

        Usuario::toggle($id);
        $user = Usuario::find($id);
        flash('success', sprintf(
            'La cuenta de %s %s.',
            $user ? $user['first_name'] . ' ' . $user['last_name'] : '',
            $user && (int) $user['active'] === 1 ? 'fue activada' : 'fue desactivada'
        ));
        redirect('usuarios/index', input('q') ? ['q' => input('q')] : []);
    }
}
