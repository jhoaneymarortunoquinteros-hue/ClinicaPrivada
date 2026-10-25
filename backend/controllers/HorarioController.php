<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Horario;
use App\Models\Medico;

/**
 * Horario laboral semanal del médico.
 *  - El médico gestiona su propio horario.
 *  - El administrador puede consultar/asignar el de cualquier médico.
 */
final class HorarioController
{
    public function index(): void
    {
        Auth::requireLogin();
        $medico = $this->resolveDoctor();
        if (!$medico) {
            flash('error', 'Selecciona un médico para gestionar su horario.');
            redirect('medicos/index');
        }

        view('doctor/horarios', [
            'title'    => 'Horarios laborales',
            'medico'   => $medico,
            'week'     => Horario::weekly((int) $medico['id']),
            'canEdit'  => $this->canEdit((int) $medico['id']),
            'doctors'  => Auth::role() === 'medico' ? [] : Medico::selectOptions(false),
            'errors'   => errors(),
        ]);
    }

    public function guardar(): void
    {
        Auth::requireLogin();
        if (!is_post()) {
            redirect('horarios/index');
        }

        $medico = $this->resolveDoctor();
        if (!$medico || !$this->canEdit((int) $medico['id'])) {
            http_response_code(403);
            view('shared/error', [
                'code' => 403, 'title' => 'Acceso denegado',
                'message' => 'No puedes modificar este horario.',
            ], 'layouts/auth');
            exit;
        }

        $weekday = (int) input('weekday', -1);
        $start = (string) input('start_time');
        $end = (string) input('end_time');
        $fields = ['doctor_id', 'weekday', 'start_time', 'end_time'];

        $errors = [];
        if ($weekday < 0 || $weekday > 6) {
            $errors['weekday'] = 'Selecciona el día de la semana.';
        }
        if ($start === '' || $end === '' || !strtotime($start) || !strtotime($end)) {
            $errors['start_time'] = 'Indica la hora de inicio y fin.';
        } elseif (strtotime($end) <= strtotime($start)) {
            $errors['end_time'] = 'La hora final debe ser posterior a la inicial.';
        } elseif (Horario::hasConflict((int) $medico['id'], $weekday, $start, $end)) {
            $errors['start_time'] = 'Ese bloque se traslapa con otro horario existente del día.';
        }

        if ($errors) {
            validation_fail($errors, $fields, 'horarios/index', ['doctor_id' => (string) $medico['id']]);
        }

        try {
            Horario::create([
                'doctor_id'  => (int) $medico['id'],
                'weekday'    => $weekday,
                'start_time' => $start,
                'end_time'   => $end,
            ]);
        } catch (\Throwable $e) {
            validation_fail(['start_time' => db_error_message($e)], $fields, 'horarios/index', ['doctor_id' => (string) $medico['id']]);
        }

        clear_form_state();
        flash('success', 'Bloque horario agregado.');
        redirect('horarios/index', ['doctor_id' => (string) $medico['id']]);
    }

    public function toggle(): void
    {
        Auth::requireLogin();
        $id = (int) input('id', 0);
        $schedule = Horario::find($id);
        if (!$schedule) {
            redirect('horarios/index');
        }
        $this->guardOwnership((int) $schedule['doctor_id']);
        Horario::toggle($id);
        flash('success', 'Bloque horario actualizado.');
        redirect('horarios/index', ['doctor_id' => (string) $schedule['doctor_id']]);
    }

    public function eliminar(): void
    {
        Auth::requireLogin();
        $id = (int) input('id', 0);
        $schedule = Horario::find($id);
        if (!$schedule) {
            redirect('horarios/index');
        }
        $this->guardOwnership((int) $schedule['doctor_id']);
        Horario::destroy($id);
        flash('success', 'Bloque horario eliminado.');
        redirect('horarios/index', ['doctor_id' => (string) $schedule['doctor_id']]);
    }

    private function resolveDoctor(): ?array
    {
        if (Auth::role() === 'medico') {
            return Medico::forUser((int) Auth::id());
        }
        Auth::requireRole(['admin']);
        $id = (int) input('doctor_id', 0);
        return $id > 0 ? Medico::find($id) : null;
    }

    private function canEdit(int $doctorId): bool
    {
        if (Auth::role() === 'admin') {
            return true;
        }
        $own = Medico::forUser((int) Auth::id());
        return $own !== null && (int) $own['id'] === $doctorId;
    }

    private function guardOwnership(int $doctorId): void
    {
        if (!$this->canEdit($doctorId)) {
            http_response_code(403);
            view('shared/error', [
                'code' => 403, 'title' => 'Acceso denegado',
                'message' => 'No puedes modificar este horario.',
            ], 'layouts/auth');
            exit;
        }
    }
}
