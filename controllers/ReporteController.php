<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Reporte;

/**
 * Reportes administrativos con filtrado dinámico (solo administrador).
 */
final class ReporteController
{
    public function __construct()
    {
        Auth::requireRole(['admin']);
    }

    /** Recopila los filtros desde la petición (compartido con csv()). */
    private function filters(): array
    {
        return [
            'date_from'   => (string) input('date_from', date('Y-m-01')),
            'date_to'     => (string) input('date_to', date('Y-m-d')),
            'doctor_id'   => (int) input('doctor_id', 0),
            'specialty_id'=> (int) input('specialty_id', 0),
            'status'      => (string) input('status'),
            'date'        => (string) input('date'),
        ];
    }

    public function index(): void
    {
        $filters = $this->filters();
        $date = $filters['date'];

        view('admin/reports', [
            'title'        => 'Reportes y estadísticas',
            'filters'      => $filters,
            'summary'      => Reporte::summary($filters),
            'byDoctor'     => Reporte::byDoctor($filters),
            'bySpecialty'  => Reporte::bySpecialty($filters),
            'agenda'       => $date !== '' ? Reporte::dailyAgenda($date, $filters['doctor_id'] ?: null) : [],
            'doctors'      => Medico::selectOptions(false),
            'specialties'  => Especialidad::all(),
            'statuses'     => \App\Models\Cita::STATUSES,
        ]);
    }

    /** Exportación CSV del detalle de citas según los filtros activos. */
    public function csv(): void
    {
        $filters = $this->filters();

        $appointments = \App\Models\Cita::list([
            'date_from'    => $filters['date_from'],
            'date_to'      => $filters['date_to'],
            'doctor_id'    => $filters['doctor_id'],
            'specialty_id' => $filters['specialty_id'],
            'status'       => $filters['status'],
        ], 5000);

        $filename = 'reporte_citas_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // BOM para que Excel reconozca UTF-8
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Fecha', 'Hora inicio', 'Hora fin', 'Estado', 'Paciente', 'Documento',
            'Médico', 'Especialidad', 'Motivo', 'Cancelación',
        ], ';');

        foreach ($appointments as $row) {
            fputcsv($out, [
                format_date($row['scheduled_date']),
                format_time($row['start_time']),
                format_time($row['end_time']),
                $row['status'],
                $row['patient_name'],
                $row['patient_document'],
                $row['doctor_name'],
                $row['specialty_name'],
                $row['reason'],
                $row['cancellation_reason'],
            ], ';');
        }
        fclose($out);
        exit;
    }
}
