<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Usuario;

/**
 * Registro de médicos con su especialidad (solo administrador).
 * Crea simultáneamente el usuario de acceso con rol "medico".
 */
final class MedicoController
{
    public function __construct()
    {
        Auth::requireRole(['admin']);
    }

    public function index(): void
    {
        $filters = [
            'q'            => (string) input('q'),
            'specialty_id' => (int) input('specialty_id', 0),
            'active'       => (string) input('active'),
        ];
        $pagination = paginate(
            Medico::count($filters),
            (int) input('page', 1),
            15
        );
        $action = (string) input('action');
        $editing = $action === 'editar' ? Medico::find((int) input('id')) : null;

        view('admin/doctors', [
            'title'        => 'Gestión de médicos',
            'doctors'      => Medico::list($filters, $pagination['per_page'], $pagination['offset']),
            'specialties'  => Especialidad::all(),
            'filters'      => $filters,
            'pagination'   => $pagination,
            'editing'      => $editing,
            'editingUser'  => $editing ? Usuario::find((int) $editing['user_id']) : null,
            'formOpen'     => in_array($action, ['nuevo', 'editar'], true) || !empty($_SESSION['errors']),
            'errors'       => errors(),
        ]);
    }

    public function guardar(): void
    {
        if (!is_post()) {
            redirect('medicos/index');
        }

        $id = (int) input('id', 0);          // id del perfil doctors
        $userId = (int) input('user_id', 0); // id del usuario (edición)
        $fields = ['id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'specialty_id',
                   'license_number', 'professional_title', 'bio', 'password', 'active'];

        $data = [
            'first_name'        => (string) input('first_name'),
            'last_name'         => (string) input('last_name'),
            'email'             => mb_strtolower((string) input('email'), 'UTF-8'),
            'phone'             => (string) input('phone'),
            'specialty_id'      => (int) input('specialty_id'),
            'license_number'    => (string) input('license_number'),
            'professional_title'=> (string) input('professional_title'),
            'bio'               => (string) input('bio'),
            'active'            => input('active') === '1' || input('active') === 'on',
        ];
        $password = (string) input('password');

        $errors = [];
        if ($data['first_name'] === '' || $data['last_name'] === '') {
            $errors['first_name'] = 'Nombre y apellidos son obligatorios.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        } elseif (Usuario::emailExists($data['email'], $userId)) {
            $errors['email'] = 'Ese correo ya está en uso.';
        }
        if ($data['specialty_id'] <= 0) {
            $errors['specialty_id'] = 'Selecciona la especialidad.';
        }
        if ($data['license_number'] === '') {
            $errors['license_number'] = 'Ingresa el número de colegiatura/licencia.';
        } elseif (Medico::licenseExists($data['license_number'], $id)) {
            $errors['license_number'] = 'Esa licencia ya está registrada.';
        }
        if ($password === '') {
            if ($id === 0) {
                $errors['password'] = 'La contraseña inicial es obligatoria.';
            }
        } else {
            $passwordError = password_error($password);
            if ($passwordError !== null) {
                $errors['password'] = $passwordError;
            }
        }

        if ($errors) {
            validation_fail($errors, $fields, 'medicos/index', $id > 0 ? ['action' => 'editar', 'id' => (string) $id] : ['action' => 'nuevo']);
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();

            if ($id > 0 && $userId > 0) {
                Usuario::update($userId, [
                    'role_id'    => Usuario::roleId('medico'),
                    'email'      => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'phone'      => $data['phone'] !== '' ? $data['phone'] : null,
                    'active'     => $data['active'],
                ], $password !== '' ? $password : null);

                Medico::update($id, [
                    'specialty_id'       => $data['specialty_id'],
                    'license_number'     => $data['license_number'],
                    'professional_title' => $data['professional_title'],
                    'bio'                => $data['bio'],
                    'active'             => $data['active'],
                ]);
                $message = 'Médico actualizado correctamente.';
            } else {
                $newUserId = Usuario::create([
                    'role_id'    => Usuario::roleId('medico'),
                    'email'      => $data['email'],
                    'password'   => $password,
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'phone'      => $data['phone'] !== '' ? $data['phone'] : null,
                    'active'     => $data['active'],
                ]);
                Medico::create([
                    'user_id'            => $newUserId,
                    'specialty_id'       => $data['specialty_id'],
                    'license_number'     => $data['license_number'],
                    'professional_title' => $data['professional_title'],
                    'bio'                => $data['bio'],
                    'active'             => $data['active'],
                ]);
                $message = 'Médico registrado correctamente.';
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            validation_fail(['email' => db_error_message($e)], $fields, 'medicos/index', ['action' => 'nuevo']);
        }

        clear_form_state();
        flash('success', $message);
        redirect('medicos/index');
    }

    public function toggle(): void
    {
        $id = (int) input('id', 0);
        if ($id <= 0) {
            redirect('medicos/index');
        }
        Medico::toggle($id);
        $medico = Medico::find($id);
        flash('success', sprintf(
            '%s %s.',
            $medico ? $medico['full_name'] : 'El médico',
            $medico && (int) $medico['active'] === 1 ? 'fue activado' : 'fue desactivado'
        ));
        redirect('medicos/index');
    }
}
