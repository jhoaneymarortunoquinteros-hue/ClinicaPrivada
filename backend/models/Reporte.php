<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Consultas para reportes administrativos y dashboards.
 */
final class Reporte extends Model
{
    /** WHERE dinámico compartido por todos los reportes (sobre v_appointments). */
    private static function filters(array $f): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($f['date_from'])) {
            $where[] = 'a.scheduled_date >= :date_from';
            $params[':date_from'] = $f['date_from'];
        }
        if (!empty($f['date_to'])) {
            $where[] = 'a.scheduled_date <= :date_to';
            $params[':date_to'] = $f['date_to'];
        }
        if (!empty($f['doctor_id'])) {
            $where[] = 'a.doctor_id = :doctor';
            $params[':doctor'] = (int) $f['doctor_id'];
        }
        if (!empty($f['specialty_id'])) {
            $where[] = 'a.specialty_id = :specialty';
            $params[':specialty'] = (int) $f['specialty_id'];
        }
        if (!empty($f['status'])) {
            $where[] = 'a.status = :status';
            $params[':status'] = $f['status'];
        }

        return [implode(' AND ', $where), $params];
    }

    /** Totales generales + desglose por estado. */
    public static function summary(array $f): array
    {
        [$where, $params] = self::filters($f);
        $rows = self::rows(
            "SELECT a.status, COUNT(*)::int AS total
               FROM v_appointments a WHERE {$where} GROUP BY a.status",
            $params
        );
        $summary = array_fill_keys(Cita::STATUSES, 0);
        foreach ($rows as $row) {
            $summary[$row['status']] = (int) $row['total'];
        }
        $summary['total'] = array_sum($summary);
        return $summary;
    }

    /** Rendimiento por médico (atendidas, canceladas, inasistentes, pendientes). */
    public static function byDoctor(array $f): array
    {
        [$where, $params] = self::filters($f);
        return self::rows(
            "SELECT a.doctor_name, a.specialty_name,
                    COUNT(*)::int AS total,
                    COUNT(*) FILTER (WHERE a.status = 'atendida')::int     AS atendidas,
                    COUNT(*) FILTER (WHERE a.status = 'cancelada')::int    AS canceladas,
                    COUNT(*) FILTER (WHERE a.status = 'inasistente')::int  AS inasistentes,
                    COUNT(*) FILTER (WHERE a.status IN ('programada','confirmada'))::int AS pendientes
               FROM v_appointments a
              WHERE {$where}
              GROUP BY a.doctor_name, a.specialty_name
              ORDER BY total DESC",
            $params
        );
    }

    /** Distribución por especialidad. */
    public static function bySpecialty(array $f): array
    {
        [$where, $params] = self::filters($f);
        return self::rows(
            "SELECT a.specialty_name, COUNT(*)::int AS total,
                    COUNT(*) FILTER (WHERE a.status = 'atendida')::int AS atendidas
               FROM v_appointments a
              WHERE {$where}
              GROUP BY a.specialty_name
              ORDER BY total DESC",
            $params
        );
    }

    /** Agenda diaria por médico (fila por hora). */
    public static function dailyAgenda(string $date, ?int $doctorId = null): array
    {
        $params = [':date' => $date];
        $extra = '';
        if ($doctorId) {
            $extra = ' AND a.doctor_id = :doctor';
            $params[':doctor'] = $doctorId;
        }
        return self::rows(
            "SELECT a.start_time, a.end_time, a.status, a.patient_name, a.patient_document,
                    a.doctor_name, a.specialty_name, a.reason
               FROM v_appointments a
              WHERE a.scheduled_date = :date{$extra}
              ORDER BY a.doctor_name, a.start_time",
            $params
        );
    }

    /** Citas atendidas de un médico en un rango (para su productividad). */
    public static function doctorProductivity(int $doctorId, string $from, string $to): array
    {
        return self::rows(
            "SELECT a.scheduled_date, COUNT(*)::int AS atendidas
               FROM v_appointments a
              WHERE a.doctor_id = :doctor AND a.status = 'atendida'
                AND a.scheduled_date BETWEEN :from AND :to
              GROUP BY a.scheduled_date
              ORDER BY a.scheduled_date",
            [':doctor' => $doctorId, ':from' => $from, ':to' => $to]
        );
    }
}
