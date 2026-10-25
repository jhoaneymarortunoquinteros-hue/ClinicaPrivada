<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Especialidad;

/**
 * CRUD de especialidades (duración de cada consulta). Solo administrador.
 */
final class EspecialidadController
{
    public function __construct()
    {
        Auth::requireRole(['admin']);
    }

    public function index(): void
    {
        $action = (string) input('action');

        view('admin/specialties', [
            'title'    => 'Especialidades',
            'list'     => Especialidad::all(),
            'editing'  => $action === 'editar' ? Especialidad::find((int) input('id')) : null,
            'formOpen' => in_array($action, ['nuevo', 'editar'], true) || !empty($_SESSION['errors']),
            'errors'   => errors(),
        ]);
    }

    public function guardar(): void
    {
        if (!is_post()) {
            redirect('especialidades/index');
        }

        $id = (int) input('id', 0);
        $fields = ['id', 'name', 'duration_minutes', 'description', 'active'];

        $data = [
            'name'             => (string) input('name'),
            'duration_minutes' => (int) input('duration_minutes', 15),
            'description'      => (string) input('description'),
            'active'           => input('active') === '1' || input('active') === 'on',
        ];

        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = 'Ingresa el nombre de la especialidad.';
        } elseif (Especialidad::nameExists($data['name'], $id)) {
            $errors['name'] = 'Ya existe una especialidad con ese nombre.';
        }
        if ($data['duration_minutes'] < 5 || $data['duration_minutes'] > 240) {
            $errors['duration_minutes'] = 'La duración debe estar entre 5 y 240 minutos.';
        }

        if ($errors) {
            validation_fail($errors, $fields, 'especialidades/index', $id > 0 ? ['action' => 'editar', 'id' => (string) $id] : ['action' => 'nuevo']);
        }

        try {
            if ($id > 0) {
                Especialidad::update($id, $data);
                $message = 'Especialidad actualizada.';
            } else {
                Especialidad::create($data);
                $message = 'Especialidad creada.';
            }
        } catch (\Throwable $e) {
            validation_fail(['name' => db_error_message($e)], $fields, 'especialidades/index', ['action' => 'nuevo']);
        }

        clear_form_state();
        flash('success', $message);
        redirect('especialidades/index');
    }

    public function eliminar(): void
    {
        $id = (int) input('id', 0);
        try {
            if (Especialidad::destroy($id)) {
                flash('success', 'Especialidad eliminada.');
            } else {
                flash('error', 'No se pudo eliminar: la especialidad está en uso por médicos o citas.');
            }
        } catch (\Throwable $e) {
            flash('error', 'No se pudo eliminar: la especialidad tiene médicos o citas asociadas.');
        }
        redirect('especialidades/index');
    }
}
