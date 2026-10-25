<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Paciente;
use App\Models\Usuario;

/**
 * Pacientes:
 *  - Recepcionista/Administrador: registro, búsqueda y edición.
 *  - Paciente: edición de sus datos personales de contacto.
 */
final class PacienteController
{
    public function index(): void
    {
        Auth::requireRole(['admin', 'recepcionista']);

        $filters = ['q' => (string) input('q')];
        $pagination = paginate(Paciente::count($filters), (int) input('page', 1), 15);
        $action = (string) input('action');

        view('receptionist/patients', [
            'title'      => 'Pacientes',
            'patients'   => Paciente::list($filters, $pagination['per_page'], $pagination['offset']),
            'filters'    => $filters,
            'pagination' => $pagination,
            'editing'    => $action === 'editar' ? Paciente::find((int) input('id')) : null,
            'errors'     => errors(),
        ]);
    }

    /** Formulario de nuevo paciente (puede venir con cita previa pendiente). */
    public function crear(): void
    {
        Auth::requireRole(['admin', 'recepcionista']);

        view('receptionist/patient-form', [
            'title'  => 'Nuevo paciente',
            'patient'=> null,
            'user'   => null,
            'errors' => errors(),
        ]);
    }

    public function editar(): void
    {
        Auth::requireRole(['admin', 'recepcionista']);

        $patient = Paciente::find((int) input('id'));
        if (!$patient) {
            flash('error', 'Paciente no encontrado.');
            redirect('pacientes/index');
        }

        view('receptionist/patient-form', [
            'title'  => 'Editar paciente',
            'patient'=> $patient,
            'user'   => Usuario::find((int) $patient['user_id']),
            'errors' => errors(),
        ]);
    }

    public function guardar(): void
    {
        Auth::requireRole(['admin', 'recepcionista']);
        if (!is_post()) {
            redirect('pacientes/index');
        }

        $id = (int) input('id', 0);
        $fields = ['id', 'first_name', 'last_name', 'email', 'phone', 'document_type', 'document_number',
                   'birth_date', 'gender', 'blood_type', 'address', 'emergency_contact_name',
                   'emergency_contact_phone', 'insurance', 'allergies', 'base_diseases',
                   'medical_background', 'password'];

        $userData = [
            'first_name' => (string) input('first_name'),
            'last_name'  => (string) input('last_name'),
            'email'      => mb_strtolower((string) input('email'), 'UTF-8'),
            'phone'      => (string) input('phone'),
            'active'     => true,
        ];
        $patientData = [
            'document_type'           => (string) input('document_type', 'DNI') ?: 'DNI',
            'document_number'         => (string) input('document_number'),
            'birth_date'              => (string) input('birth_date'),
            'gender'                  => (string) input('gender'),
            'blood_type'              => (string) input('blood_type'),
            'address'                 => (string) input('address'),
            'emergency_contact_name'  => (string) input('emergency_contact_name'),
            'emergency_contact_phone' => (string) input('emergency_contact_phone'),
            'insurance'               => (string) input('insurance'),
            'allergies'               => (string) input('allergies'),
            'base_diseases'           => (string) input('base_diseases'),
            'medical_background'      => (string) input('medical_background'),
        ];
        $password = (string) input('password');

        $existing = $id > 0 ? Paciente::find($id) : null;
        $errors = [];
        if ($userData['first_name'] === '' || $userData['last_name'] === '') {
            $errors['first_name'] = 'Nombre y apellidos son obligatorios.';
        }
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        } elseif (Usuario::emailExists($userData['email'], $existing ? (int) $existing['user_id'] : 0)) {
            $errors['email'] = 'Ese correo ya está registrado.';
        }
        if ($patientData['document_number'] === '') {
            $errors['document_number'] = 'Ingresa el número de documento.';
        } elseif (Paciente::documentExists($patientData['document_number'], $id)) {
            $errors['document_number'] = 'Ya existe un paciente con ese documento.';
        }
        if ($patientData['birth_date'] !== '' && !strtotime($patientData['birth_date'])) {
            $errors['birth_date'] = 'Fecha de nacimiento inválida.';
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
            validation_fail($errors, $fields, $id > 0 ? 'pacientes/editar' : 'pacientes/crear', $id > 0 ? ['id' => (string) $id] : []);
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();

            if ($existing) {
                Usuario::update((int) $existing['user_id'], [
                    'role_id'    => Usuario::roleId('paciente'),
                    'email'      => $userData['email'],
                    'first_name' => $userData['first_name'],
                    'last_name'  => $userData['last_name'],
                    'phone'      => $userData['phone'] !== '' ? $userData['phone'] : null,
                    'active'     => true,
                ], $password !== '' ? $password : null);
                Paciente::update($id, $patientData);
                $message = 'Paciente actualizado correctamente.';
            } else {
                $userId = Usuario::create([
                    'role_id'    => Usuario::roleId('paciente'),
                    'email'      => $userData['email'],
                    'password'   => $password,
                    'first_name' => $userData['first_name'],
                    'last_name'  => $userData['last_name'],
                    'phone'      => $userData['phone'] !== '' ? $userData['phone'] : null,
                    'active'     => true,
                ]);
                $patientData['user_id'] = $userId;
                Paciente::create($patientData);
                $message = 'Paciente registrado correctamente.';
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            validation_fail(['email' => db_error_message($e)], $fields, 'pacientes/crear');
        }

        clear_form_state();
        flash('success', $message);
        redirect('pacientes/index');
    }

    /** Perfil personal del paciente en sesión. */
    public function perfil(): void
    {
        Auth::requireRole(['paciente']);

        view('patient/profile', [
            'title'   => 'Mi perfil',
            'patient' => Paciente::findByUserId((int) Auth::id()),
            'errors'  => errors(),
        ]);
    }

    public function guardarPerfil(): void
    {
        Auth::requireRole(['paciente']);
        if (!is_post()) {
            redirect('pacientes/perfil');
        }

        $patient = Paciente::findByUserId((int) Auth::id());
        if (!$patient) {
            flash('error', 'No se encontró tu perfil de paciente.');
            redirect('dashboard/index');
        }

        $fields = ['first_name', 'last_name', 'phone', 'address', 'emergency_contact_name',
                   'emergency_contact_phone', 'birth_date', 'gender', 'document_number'];
        $errors = [];

        $first = (string) input('first_name');
        $last = (string) input('last_name');
        $email = mb_strtolower((string) input('email'), 'UTF-8');
        $document = (string) input('document_number');
        $password = (string) input('password');

        if ($first === '' || $last === '') {
            $errors['first_name'] = 'Nombre y apellidos son obligatorios.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        } elseif (Usuario::emailExists($email, (int) $patient['user_id'])) {
            $errors['email'] = 'Ese correo ya está en uso.';
        }
        if ($document === '') {
            $errors['document_number'] = 'El número de documento es obligatorio.';
        } elseif (Paciente::documentExists($document, (int) $patient['id'])) {
            $errors['document_number'] = 'Ya existe un paciente con ese documento.';
        }
        if ($password !== '') {
            $passwordError = password_error($password);
            if ($passwordError !== null) {
                $errors['password'] = $passwordError;
            }
        }

        if ($errors) {
            validation_fail($errors, $fields, 'pacientes/perfil');
        }

        try {
            Usuario::update((int) $patient['user_id'], [
                'role_id'    => Usuario::roleId('paciente'),
                'email'      => $email,
                'first_name' => $first,
                'last_name'  => $last,
                'phone'      => (string) input('phone'),
                'active'     => true,
            ], $password !== '' ? $password : null);

            Paciente::update((int) $patient['id'], [
                'document_type'           => $patient['document_type'],
                'document_number'         => $document,
                'birth_date'              => (string) input('birth_date', (string) $patient['birth_date']),
                'gender'                  => (string) input('gender', (string) $patient['gender']),
                'blood_type'              => (string) $patient['blood_type'],
                'address'                 => (string) input('address'),
                'emergency_contact_name'  => (string) input('emergency_contact_name'),
                'emergency_contact_phone' => (string) input('emergency_contact_phone'),
                'insurance'               => (string) $patient['insurance'],
                'allergies'               => (string) $patient['allergies'],
                'base_diseases'           => (string) $patient['base_diseases'],
                'medical_background'      => (string) $patient['medical_background'],
            ]);
        } catch (\Throwable $e) {
            validation_fail(['email' => db_error_message($e)], $fields, 'pacientes/perfil');
        }

        // Actualizar el nombre visible en la sesión.
        $_SESSION['auth']['first_name'] = $first;
        $_SESSION['auth']['last_name'] = $last;
        $_SESSION['auth']['email'] = $email;

        clear_form_state();
        flash('success', 'Perfil actualizado correctamente.');
        redirect('pacientes/perfil');
    }
}
