<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Cita;
use App\Models\Historial;
use App\Models\Medico;
use App\Models\Paciente;

/**
 * Historial clínico digital.
 *  - Médico: consulta el historial de sus pacientes y registra la evolución.
 *  - Paciente: visualiza su propio historial.
 *  - Admin/Recepción: consulta de solo lectura.
 */
final class HistorialController
{
    /** Pacientes que han tenido (o tienen) citas con el médico en sesión. */
    public function pacientes(): void
    {
        Auth::requireRole(['medico']);
        $medico = Medico::forUser((int) Auth::id());
        if (!$medico) {
            flash('error', 'No tienes un perfil médico asociado.');
            redirect('dashboard/index');
        }

        $q = (string) input('q');

        view('doctor/patients', [
            'title'    => 'Mis pacientes',
            'medico'   => $medico,
            'patients' => Paciente::forDoctor((int) $medico['id'], $q),
            'q'        => $q,
        ]);
    }

    /** Historial clínico completo de un paciente. */
    public function ver(): void
    {
        Auth::requireRole(['medico', 'admin', 'recepcionista']);

        $patientId = (int) input('patient_id', 0);
        $patient = $patientId > 0 ? Paciente::find($patientId) : null;
        if (!$patient) {
            flash('error', 'Paciente no encontrado.');
            redirect('pacientes/index');
        }

        $appointmentId = (int) input('appointment_id', 0);
        $appointment = $appointmentId > 0 ? Cita::find($appointmentId) : null;

        // Un médico solo puede ver pacientes asignados (con citas con él).
        if (Auth::role() === 'medico') {
            $medico = Medico::forUser((int) Auth::id());
            if (!$medico || !Cita::count(['doctor_id' => (int) $medico['id'], 'patient_id' => $patientId])) {
                http_response_code(403);
                view('shared/error', [
                    'code' => 403, 'title' => 'Acceso denegado',
                    'message' => 'Este paciente no tiene citas contigo.',
                ], 'layouts/auth');
                exit;
            }
        }

        view('doctor/patient-record', [
            'title'        => 'Historial de ' . $patient['full_name'],
            'patient'      => $patient,
            'records'      => Historial::forPatient($patientId, 50),
            'appointment'  => $appointment,
            'appointmentId'=> $appointmentId,
            'canEdit'      => Auth::role() === 'medico',
            'errors'       => errors(),
        ]);
    }

    /** Registra la evolución médica de una consulta. */
    public function guardar(): void
    {
        Auth::requireRole(['medico']);
        if (!is_post()) {
            redirect('historial/pacientes');
        }

        $medico = Medico::forUser((int) Auth::id());
        $patientId = (int) input('patient_id', 0);
        $appointmentId = (int) input('appointment_id', 0);
        $fields = ['patient_id', 'appointment_id', 'consultation_date', 'chief_complaint',
                   'diagnosis', 'treatment', 'evolution', 'follow_up_date'];

        if (!$medico) {
            flash('error', 'No tienes un perfil médico asociado.');
            redirect('dashboard/index');
        }

        $patient = Paciente::find($patientId);
        if (!$patient) {
            flash('error', 'Paciente no encontrado.');
            redirect('historial/pacientes');
        }

        $errors = [];
        if ((string) input('diagnosis') === '' && (string) input('evolution') === '') {
            $errors['diagnosis'] = 'Registra al menos el diagnóstico o la evolución de la consulta.';
        }
        $date = (string) input('consultation_date') ?: date('Y-m-d');
        if (!strtotime($date)) {
            $errors['consultation_date'] = 'Fecha de consulta inválida.';
        }

        // Si se asocia una cita, debe pertenecer a este médico.
        if ($appointmentId > 0) {
            $appointment = Cita::find($appointmentId);
            if (!$appointment || (int) $appointment['doctor_id'] !== (int) $medico['id']
                || (int) $appointment['patient_id'] !== $patientId) {
                $errors['appointment_id'] = 'La cita seleccionada no corresponde a este paciente y médico.';
            }
        }

        if ($errors) {
            validation_fail($errors, $fields, 'historial/ver', [
                'patient_id'    => (string) $patientId,
                'appointment_id'=> (string) $appointmentId,
            ]);
        }

        Historial::create([
            'patient_id'        => $patientId,
            'doctor_id'         => (int) $medico['id'],
            'appointment_id'    => $appointmentId ?: null,
            'consultation_date' => $date,
            'chief_complaint'   => (string) input('chief_complaint'),
            'diagnosis'         => (string) input('diagnosis'),
            'treatment'         => (string) input('treatment'),
            'evolution'         => (string) input('evolution'),
            'follow_up_date'    => (string) input('follow_up_date'),
        ]);

        // Si la cita asociada aún estaba pendiente, se marca como atendida.
        if ($appointmentId > 0) {
            $appointment = Cita::find($appointmentId);
            if ($appointment && in_array($appointment['status'], ['programada', 'confirmada'], true)) {
                try {
                    Cita::setStatus($appointmentId, 'atendida');
                } catch (\Throwable) {
                    // La evolución ya quedó registrada; el estado lo ajusta el médico.
                }
            }
        }

        clear_form_state();
        flash('success', 'Evolución médica registrada correctamente.');
        redirect('historial/ver', ['patient_id' => (string) $patientId]);
    }

    /** El paciente consulta su propio historial digital. */
    public function miHistorial(): void
    {
        Auth::requireRole(['paciente']);

        $patient = Paciente::findByUserId((int) Auth::id());
        if (!$patient) {
            view('shared/error', [
                'code' => 403, 'title' => 'Perfil incompleto',
                'message' => 'Tu usuario no tiene un perfil de paciente asociado.',
            ], 'layouts/auth');
            return;
        }

        view('patient/records', [
            'title'    => 'Mi historial clínico',
            'patient'  => $patient,
            'records'  => Historial::forPatient((int) $patient['id'], 100),
        ]);
    }
}
