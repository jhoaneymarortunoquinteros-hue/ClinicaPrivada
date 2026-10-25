<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use DomainException;

/**
 * Gestión completa de citas: programar, reprogramar, cancelar y
 * registrar estados (asistencia/inasistencia).
 */
final class CitaController
{
    /** Listado principal: cambia de vista según el rol. */
    public function index(): void
    {
        Auth::requireLogin();
        $role = Auth::role();

        if ($role === 'paciente') {
            $patient = Paciente::findByUserId((int) Auth::id());
            if (!$patient) {
                view('shared/error', [
                    'code' => 403, 'title' => 'Perfil incompleto',
                    'message' => 'Tu usuario no tiene un perfil de paciente asociado.',
                ], 'layouts/auth');
                return;
            }
            $filters = [
                'patient_id' => (int) $patient['id'],
                'status'     => (string) input('status'),
                'date_from'  => (string) input('date_from'),
                'date_to'    => (string) input('date_to'),
            ];
            $pagination = paginate(Cita::count($filters), (int) input('page', 1), 10);

            view('patient/appointments', [
                'title'      => 'Mis citas',
                'patient'    => $patient,
                'appointments' => Cita::list($filters, $pagination['per_page'], $pagination['offset']),
                'filters'    => $filters,
                'pagination' => $pagination,
                'statuses'   => Cita::STATUSES,
            ]);
            return;
        }

        if ($role === 'medico') {
            $medico = Medico::forUser((int) Auth::id());
            if (!$medico) {
                view('shared/error', [
                    'code' => 403, 'title' => 'Perfil médico incompleto',
                    'message' => 'Tu usuario no tiene un perfil de médico asociado.',
                ], 'layouts/auth');
                return;
            }
            $date = (string) input('date') ?: date('Y-m-d');
            $status = (string) input('status');

            $dayFilters = ['doctor_id' => (int) $medico['id'], 'date' => $date];
            if ($status !== '') {
                $dayFilters['status'] = $status;
            }

            view('doctor/agenda', [
                'title'        => 'Mi agenda',
                'medico'       => $medico,
                'date'         => $date,
                'status'       => $status,
                'appointments' => Cita::list($dayFilters, 50),
                'todaySummary' => Cita::statusSummary(['doctor_id' => (int) $medico['id'], 'date' => $date]),
                'statuses'     => Cita::STATUSES,
            ]);
            return;
        }

        // Administrador / recepcionista
        $filters = [
            'q'         => (string) input('q'),
            'status'    => (string) input('status'),
            'doctor_id' => (int) input('doctor_id', 0),
            'date'      => (string) input('date'),
            'date_from' => (string) input('date_from'),
            'date_to'   => (string) input('date_to'),
        ];
        $pagination = paginate(Cita::count($filters), (int) input('page', 1), 15);

        view('receptionist/appointments', [
            'title'        => 'Gestión de citas',
            'appointments' => Cita::list($filters, $pagination['per_page'], $pagination['offset']),
            'filters'      => $filters,
            'pagination'   => $pagination,
            'doctors'      => Medico::selectOptions(),
            'statuses'     => Cita::STATUSES,
            'summary'      => Cita::statusSummary($filters),
        ]);
    }

    /** Formulario para programar una cita. */
    public function crear(): void
    {
        Auth::requireRole(['admin', 'recepcionista', 'paciente']);
        $isPatient = Auth::role() === 'paciente';

        $patient = null;
        $patientId = (int) input('patient_id', 0);
        if ($isPatient) {
            $patient = Paciente::findByUserId((int) Auth::id());
            if (!$patient) {
                view('shared/error', [
                    'code' => 403, 'title' => 'Perfil incompleto',
                    'message' => 'Tu usuario no tiene un perfil de paciente asociado.',
                ], 'layouts/auth');
                return;
            }
            $patientId = (int) $patient['id'];
        } elseif ($patientId > 0) {
            $patient = Paciente::find($patientId);
        }

        $date = (string) input('date') ?: date('Y-m-d');
        $doctorId = (int) input('doctor_id', 0);
        $doctors = Medico::selectOptions();
        if ($doctorId <= 0 && $doctors) {
            $doctorId = (int) $doctors[0]['id'];
        }

        view('shared/appointment-form', [
            'title'            => 'Programar cita',
            'mode'             => 'crear',
            'appointment'      => null,
            'doctors'          => $doctors,
            'patients'         => $isPatient ? [] : Paciente::list([], 500),
            'patient'          => $patient,
            'canChoosePatient' => !$isPatient,
            'selectedPatient'  => $patientId,
            'selectedDoctor'   => $doctorId,
            'date'             => $date,
            'slots'            => $doctorId > 0 ? Cita::slots($doctorId, $date) : [],
            'errors'           => errors(),
        ]);
    }

    /** Persiste la nueva cita. */
    public function guardar(): void
    {
        Auth::requireRole(['admin', 'recepcionista', 'paciente']);
        if (!is_post()) {
            redirect('citas/index');
        }

        $isPatient = Auth::role() === 'paciente';
        $patientId = $isPatient
            ? (int) (Paciente::findByUserId((int) Auth::id())['id'] ?? 0)
            : (int) input('patient_id', 0);

        $data = [
            'patient_id'     => $patientId,
            'doctor_id'      => (int) input('doctor_id', 0),
            'scheduled_date' => (string) input('scheduled_date'),
            'start_time'     => (string) input('start_time'),
            'end_time'       => (string) input('end_time'),
            'reason'         => (string) input('reason'),
            'notes'          => (string) input('notes'),
        ];
        if ($data['end_time'] === '' && $data['start_time'] !== '' && $data['doctor_id'] > 0) {
            // La hora final se deriva de la duración de la especialidad.
            $data['end_time'] = Cita::endFromStart($data['doctor_id'], $data['start_time']);
        }

        $fields = ['patient_id', 'doctor_id', 'scheduled_date', 'start_time', 'end_time', 'reason', 'notes'];
        $errors = Cita::validate($data);

        if ($errors) {
            validation_fail($errors, $fields, 'citas/crear', [
                'doctor_id'  => (string) $data['doctor_id'],
                'date'       => $data['scheduled_date'],
                'patient_id' => (string) $patientId,
            ]);
        }

        try {
            Cita::create($data + ['created_by' => Auth::id()]);
        } catch (DomainException $e) {
            validation_fail(['start_time' => $e->getMessage()], $fields, 'citas/crear', [
                'doctor_id'  => (string) $data['doctor_id'],
                'date'       => $data['scheduled_date'],
                'patient_id' => (string) $patientId,
            ]);
        }

        clear_form_state();
        flash('success', 'Cita programada correctamente para el ' . format_date($data['scheduled_date'])
            . ' a las ' . format_time($data['start_time']) . '.');
        redirect('citas/index');
    }

    /** Formulario de reprogramación. */
    public function reprogramar(): void
    {
        Auth::requireRole(['admin', 'recepcionista']);

        $appointment = Cita::find((int) input('id', 0));
        if (!$appointment) {
            flash('error', 'La cita no existe.');
            redirect('citas/index');
        }

        $date = (string) input('date') ?: (string) $appointment['scheduled_date'];
        $doctorId = (int) $appointment['doctor_id'];

        view('shared/appointment-form', [
            'title'            => 'Reprogramar cita',
            'mode'             => 'reprogramar',
            'appointment'      => $appointment,
            'doctors'          => Medico::selectOptions(),
            'patients'         => Paciente::list([], 500),
            'patient'          => Paciente::find((int) $appointment['patient_id']),
            'canChoosePatient' => false,
            'selectedPatient'  => (int) $appointment['patient_id'],
            'selectedDoctor'   => $doctorId,
            'date'             => $date,
            'slots'            => Cita::slots($doctorId, $date),
            'errors'           => errors(),
        ]);
    }

    public function guardarReprogramacion(): void
    {
        Auth::requireRole(['admin', 'recepcionista']);
        if (!is_post()) {
            redirect('citas/index');
        }

        $id = (int) input('id', 0);
        $appointment = Cita::find($id);
        if (!$appointment) {
            flash('error', 'La cita no existe.');
            redirect('citas/index');
        }

        $data = [
            'patient_id'     => (int) $appointment['patient_id'],
            'doctor_id'      => (int) $appointment['doctor_id'],
            'scheduled_date' => (string) input('scheduled_date'),
            'start_time'     => (string) input('start_time'),
            'end_time'       => (string) input('end_time'),
        ];
        if ($data['end_time'] === '' && $data['start_time'] !== '') {
            $data['end_time'] = Cita::endFromStart($data['doctor_id'], $data['start_time']);
        }
        $fields = ['id', 'scheduled_date', 'start_time', 'end_time', 'notes'];
        $errors = Cita::validate($data, $id);

        if ($errors) {
            validation_fail($errors, $fields, 'citas/reprogramar', ['id' => (string) $id, 'date' => $data['scheduled_date']]);
        }

        try {
            Cita::reprogram($id, $data['scheduled_date'], $data['start_time'], $data['end_time'], (string) input('notes'));
            if (in_array($appointment['status'], ['cancelada', 'inasistente'], true)) {
                Cita::setStatus($id, 'programada');
            }
        } catch (DomainException $e) {
            validation_fail(['start_time' => $e->getMessage()], $fields, 'citas/reprogramar', ['id' => (string) $id]);
        }

        clear_form_state();
        flash('success', 'Cita reprogramada correctamente.');
        redirect('citas/index');
    }

    /** Cancelación con motivo (recepción/admin o el propio paciente). */
    public function cancelar(): void
    {
        Auth::requireLogin();
        if (!is_post()) {
            redirect('citas/index');
        }

        $id = (int) input('id', 0);
        $appointment = Cita::find($id);
        if (!$appointment) {
            flash('error', 'La cita no existe.');
            redirect('citas/index');
        }
        $this->authorize($appointment);

        $reason = (string) input('reason');
        if ($reason === '') {
            flash('error', 'Indica el motivo de la cancelación.');
            redirect('citas/index', ['id' => (string) $id, 'cancel' => (string) $id]);
        }

        try {
            Cita::setStatus($id, 'cancelada', $reason);
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect('citas/index');
        }

        flash('success', 'Cita cancelada correctamente.');
        redirect('citas/index');
    }

    /** Cambio de turno por parte del médico (confirmar/atendida/inasistente). */
    public function estado(): void
    {
        Auth::requireRole(['medico', 'admin', 'recepcionista']);
        if (!is_post()) {
            redirect('citas/index');
        }

        $id = (int) input('id', 0);
        $status = (string) input('status');
        $appointment = Cita::find($id);

        if (!$appointment) {
            flash('error', 'La cita no existe.');
            redirect('citas/index');
        }
        $this->authorize($appointment);

        try {
            Cita::setStatus($id, $status, (string) input('reason') ?: null);
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect('citas/index', ['date' => (string) $appointment['scheduled_date']]);
        }

        flash('success', 'Estado de la cita actualizado a "' . $status . '".');
        redirect('citas/index', ['date' => (string) $appointment['scheduled_date']]);
    }

    /** API JSON: franjas libres para un médico/fecha (select dinámico). */
    public function disponibilidad(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $doctorId = (int) input('doctor_id', 0);
        $date = (string) input('date');
        $duration = $doctorId > 0 ? Especialidad::durationOf(
            (int) (Medico::find($doctorId)['specialty_id'] ?? 0)
        ) : 0;

        echo json_encode([
            'doctor_id' => $doctorId,
            'date'      => $date,
            'duration'  => $duration,
            'slots'     => $doctorId > 0 && $date !== '' ? Cita::slots($doctorId, $date) : [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Reglas de acceso por cita:
     *  - admin/recepcionista: cualquier cita.
     *  - medico: solo citas de su propio perfil médico.
     *  - paciente: solo sus propias citas.
     */
    private function authorize(array $appointment): void
    {
        $role = Auth::role();

        if (in_array($role, ['admin', 'recepcionista'], true)) {
            return;
        }

        if ($role === 'medico') {
            $medico = Medico::forUser((int) Auth::id());
            if ($medico && (int) $medico['id'] === (int) $appointment['doctor_id']) {
                return;
            }
        }

        if ($role === 'paciente') {
            $patient = Paciente::findByUserId((int) Auth::id());
            if ($patient && (int) $patient['id'] === (int) $appointment['patient_id']) {
                return;
            }
        }

        http_response_code(403);
        view('shared/error', [
            'code'    => 403,
            'title'   => 'Acceso denegado',
            'message' => 'No tienes permisos para modificar esta cita.',
        ], 'layouts/auth');
        exit;
    }
}
