<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Cita;
use App\Models\Historial;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Reporte;
use App\Models\Usuario;

/**
 * Panel principal: el contenido cambia según el rol en sesión.
 */
final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        match (Auth::role()) {
            'admin'         => $this->admin(),
            'recepcionista' => $this->recepcionista(),
            'medico'        => $this->medico(),
            'paciente'      => $this->paciente(),
            default         => view('shared/error', [
                'code'    => 403,
                'title'   => 'Rol no reconocido',
                'message' => 'Tu usuario no tiene un rol válido asignado.',
            ], 'layouts/auth'),
        };
    }

    private function admin(): void
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        view('admin/dashboard', [
            'title'        => 'Panel del administrador',
            'stats'        => [
                'users'        => Usuario::count([]),
                'active_users' => Usuario::totalActive(),
                'doctors'      => Medico::count([]),
                'patients'     => Paciente::total(),
                'today'        => Cita::count(['date' => $today]),
            ],
            'todaySummary' => Cita::statusSummary(['date' => $today]),
            'monthSummary' => Cita::statusSummary(['date_from' => $monthStart, 'date_to' => date('Y-m-t')]),
            'byDoctor'     => Reporte::byDoctor(['date_from' => $monthStart, 'date_to' => date('Y-m-t')]),
            'recentUsers'  => Usuario::list([], 6),
        ]);
    }

    private function recepcionista(): void
    {
        $today = date('Y-m-d');

        view('receptionist/dashboard', [
            'title'        => 'Panel de recepción',
            'todaySummary' => Cita::statusSummary(['date' => $today]),
            'patients'     => Paciente::total(),
            'todayList'    => array_slice(Cita::list(['date' => $today], 10), 0),
            'upcoming'     => Cita::list(['date_from' => $today], 8),
        ]);
    }

    private function medico(): void
    {
        $medico = Medico::forUser((int) Auth::id());
        if (!$medico) {
            view('shared/error', [
                'code'    => 403,
                'title'   => 'Perfil médico incompleto',
                'message' => 'Tu usuario no tiene un perfil de médico asociado. Contacta al administrador.',
            ], 'layouts/auth');
            return;
        }

        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-6 days'));

        view('doctor/dashboard', [
            'title'        => 'Panel del médico',
            'medico'       => $medico,
            'todayList'    => Cita::forDoctorDate((int) $medico['id'], $today),
            'todaySummary' => Cita::statusSummary(['doctor_id' => (int) $medico['id'], 'date' => $today]),
            'weekSummary'  => Cita::statusSummary(['doctor_id' => (int) $medico['id'], 'date_from' => $weekStart, 'date_to' => $today]),
        ]);
    }

    private function paciente(): void
    {
        $patient = Paciente::findByUserId((int) Auth::id());
        if (!$patient) {
            view('shared/error', [
                'code'    => 403,
                'title'   => 'Perfil de paciente incompleto',
                'message' => 'Tu usuario no tiene un perfil de paciente asociado. Contacta a la clínica.',
            ], 'layouts/auth');
            return;
        }

        view('patient/dashboard', [
            'title'        => 'Mi panel',
            'patient'      => $patient,
            'next'         => Cita::upcomingForPatient((int) $patient['id'], 5),
            'history'      => Cita::list(['patient_id' => (int) $patient['id'], 'status' => 'atendida'], 5),
            'latestRecord' => Historial::latestForPatient((int) $patient['id']),
        ]);
    }
}
