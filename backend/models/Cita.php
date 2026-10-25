<?php
declare(strict_types=1);

namespace App\Models;

use DomainException;
use PDOException;

/**
 * Citas médicas: consultas, validación de disponibilidad y estados.
 *
 * Reglas de negocio (duplicadas en el trigger fn_appointments_validate
 * para que la BD también las garantice):
 *  - Duración exacta según la especialidad del médico.
 *  - Solo dentro del horario laboral del médico (días disponibles).
 *  - Sin traslapes para el mismo médico.
 *  - Sin fechas pasadas al momento de crear.
 */
final class Cita extends Model
{
    public const STATUSES = ['programada', 'confirmada', 'atendida', 'cancelada', 'inasistente'];

    private static function filters(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['doctor_id'])) {
            $where[] = 'a.doctor_id = :doctor';
            $params[':doctor'] = (int) $filters['doctor_id'];
        }
        if (!empty($filters['patient_id'])) {
            $where[] = 'a.patient_id = :patient';
            $params[':patient'] = (int) $filters['patient_id'];
        }
        if (!empty($filters['user_id'])) {
            // citas del médico cuyo usuario es este id
            $where[] = 'd.user_id = :user';
            $params[':user'] = (int) $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'a.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'a.scheduled_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'a.scheduled_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['date'])) {
            $where[] = 'a.scheduled_date = :date';
            $params[':date'] = $filters['date'];
        }
        if (!empty($filters['specialty_id'])) {
            $where[] = 'a.specialty_id_of_doctor = :specialty';
            $params[':specialty'] = (int) $filters['specialty_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = "(lower(a.patient_name || ' ' || a.patient_document || ' ' || a.doctor_name) LIKE :q)";
            $params[':q'] = '%' . mb_strtolower((string) $filters['q'], 'UTF-8') . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    public static function list(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $params] = self::filters($filters);
        $sql = "SELECT a.*, a.id AS appointment_id
                  FROM v_appointments a
                  JOIN doctors d ON d.id = a.doctor_id
                 WHERE {$where}
                 ORDER BY a.scheduled_date DESC, a.start_time DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }
        return self::rows($sql, $params);
    }

    public static function count(array $filters = []): int
    {
        [$where, $params] = self::filters($filters);
        return (int) self::scalar(
            "SELECT COUNT(*) FROM v_appointments a JOIN doctors d ON d.id = a.doctor_id WHERE {$where}",
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return self::row('SELECT * FROM v_appointments WHERE id = :id', [':id' => $id]);
    }

    /**
     * Valida reglas de negocio antes de crear/reprogramar.
     * @return array<string, string> errores por campo
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        $patientId = (int) ($data['patient_id'] ?? 0);
        $doctorId = (int) ($data['doctor_id'] ?? 0);
        $date = (string) ($data['scheduled_date'] ?? '');
        $start = (string) ($data['start_time'] ?? '');
        $end = (string) ($data['end_time'] ?? '');

        if ($patientId <= 0) {
            $errors['patient_id'] = 'Selecciona el paciente.';
        }
        if ($doctorId <= 0) {
            $errors['doctor_id'] = 'Selecciona el médico.';
        }
        if ($date === '' || !strtotime($date)) {
            $errors['scheduled_date'] = 'Selecciona una fecha válida.';
        }
        if ($start === '' || $end === '') {
            $errors['start_time'] = 'Selecciona la hora de inicio y fin.';
            return $errors;
        }
        if ($errors) {
            return $errors;
        }

        $doctor = Medico::find($doctorId);
        if (!$doctor || !(int) $doctor['active']) {
            $errors['doctor_id'] = 'El médico seleccionado no está disponible.';
            return $errors;
        }

        // 1. Duración exacta según especialidad
        $duration = (int) $doctor['duration_minutes'];
        $minutes = (strtotime($end) - strtotime($start)) / 60;
        if ($minutes <= 0) {
            $errors['end_time'] = 'La hora final debe ser posterior a la hora inicial.';
            return $errors;
        }
        if ((int) $minutes !== $duration) {
            $errors['start_time'] = sprintf(
                'La consulta debe durar exactamente %d minutos según la especialidad %s.',
                $duration,
                $doctor['specialty_name']
            );
        }

        // 2. Sin fechas pasadas
        if ($date < date('Y-m-d')) {
            $errors['scheduled_date'] = 'No se pueden agendar citas en fechas pasadas.';
        }

        // 3. Día y horario laboral
        $weekday = (int) date('w', strtotime($date));
        if (!self::horarioCubre($doctorId, $weekday, $start, $end)) {
            $errors['scheduled_date'] = sprintf(
                'El médico no atiende ese día u horario (%s). Revisa su horario laboral.',
                Horario::$days[$weekday] ?? 'día no disponible'
            );
        }

        // 4. Traslapes con otras citas del mismo médico
        if (self::hasOverlap($doctorId, $date, $start, $end, $excludeId)) {
            $errors['start_time'] = 'El médico ya tiene una cita que se traslapa con ese horario. Elige otra hora.';
        }

        return $errors;
    }

    /** Calcula la hora final a partir de la inicial y la duración de la especialidad. */
    public static function endFromStart(int $doctorId, string $start): string
    {
        $duration = (int) (self::scalar(
            'SELECT sp.duration_minutes FROM doctors d
               JOIN specialties sp ON sp.id = d.specialty_id
              WHERE d.id = :doctor',
            [':doctor' => $doctorId]
        ) ?? 15);

        $ts = strtotime($start);
        return $ts ? date('H:i:s', $ts + $duration * 60) : '';
    }

    public static function hasOverlap(int $doctorId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        return (bool) self::scalar(
            "SELECT COUNT(*) FROM appointments
              WHERE doctor_id = :doctor AND scheduled_date = :date
                AND status <> 'cancelada'
                AND start_time < :end AND end_time > :start
                AND id <> :exclude",
            [
                ':doctor'  => $doctorId,
                ':date'    => $date,
                ':start'   => $start,
                ':end'     => $end,
                ':exclude' => $excludeId ?? 0,
            ]
        );
    }

    public static function horarioCubre(int $doctorId, int $weekday, string $start, string $end): bool
    {
        return Horario::covers($doctorId, $weekday, $start, $end);
    }

    /** Franja horaria completa (libre u ocupada) para un médico en una fecha. */
    public static function slots(int $doctorId, string $date): array
    {
        if ($date === '' || !strtotime($date)) {
            return [];
        }

        $weekday = (int) date('w', strtotime($date));
        $schedules = self::rows(
            'SELECT start_time, end_time FROM doctor_schedules
              WHERE doctor_id = :doctor AND weekday = :weekday AND is_active = TRUE
              ORDER BY start_time',
            [':doctor' => $doctorId, ':weekday' => $weekday]
        );
        if (!$schedules) {
            return [];
        }

        $duration = (int) (self::scalar(
            'SELECT sp.duration_minutes FROM doctors d
               JOIN specialties sp ON sp.id = d.specialty_id
              WHERE d.id = :doctor',
            [':doctor' => $doctorId]
        ) ?? 15);

        $booked = self::rows(
            "SELECT start_time, end_time FROM appointments
              WHERE doctor_id = :doctor AND scheduled_date = :date AND status <> 'cancelada'",
            [':doctor' => $doctorId, ':date' => $date]
        );

        $today = date('Y-m-d');
        $now = date('H:i:s');
        $step = $duration * 60;
        $slots = [];

        foreach ($schedules as $schedule) {
            $cursor = strtotime($schedule['start_time']);
            $limit = strtotime($schedule['end_time']);
            while ($cursor + $step <= $limit) {
                $startTs = $cursor;
                $endTs = $cursor + $step;
                $startStr = date('H:i:s', $startTs);
                $endStr = date('H:i:s', $endTs);

                $free = true;
                foreach ($booked as $booking) {
                    if ($booking['start_time'] < $endStr && $booking['end_time'] > $startStr) {
                        $free = false;
                        break;
                    }
                }

                $isFuture = ($date > $today) || ($date === $today && $startStr >= $now);
                if ($free && $isFuture) {
                    $slots[] = [
                        'start' => date('H:i', $startTs),
                        'end'   => date('H:i', $endTs),
                        'label' => date('H:i', $startTs) . ' - ' . date('H:i', $endTs),
                    ];
                }
                $cursor += $step;
            }
        }

        return $slots;
    }

    public static function create(array $data): int
    {
        try {
            return self::insert(
                'INSERT INTO appointments (patient_id, doctor_id, scheduled_date, start_time, end_time,
                                           status, reason, notes, created_by)
                 VALUES (:patient_id, :doctor_id, :scheduled_date, :start_time, :end_time,
                         :status, :reason, :notes, :created_by)
                 RETURNING id',
                [
                    ':patient_id'     => (int) $data['patient_id'],
                    ':doctor_id'      => (int) $data['doctor_id'],
                    ':scheduled_date' => $data['scheduled_date'],
                    ':start_time'     => $data['start_time'],
                    ':end_time'       => $data['end_time'],
                    ':status'         => $data['status'] ?? 'programada',
                    ':reason'         => $data['reason'] ?? null,
                    ':notes'          => $data['notes'] ?? null,
                    ':created_by'     => $data['created_by'] ?? null,
                ]
            );
        } catch (PDOException $e) {
            throw new DomainException(db_error_message($e), 0, $e);
        }
    }

    public static function reprogram(int $id, string $date, string $start, string $end, ?string $notes = null): bool
    {
        try {
            return self::run(
                'UPDATE appointments
                    SET scheduled_date = :date, start_time = :start, end_time = :end,
                        notes = COALESCE(:notes, notes),
                        status = CASE WHEN status IN (\'cancelada\', \'atendida\') THEN status ELSE \'programada\' END
                  WHERE id = :id',
                [':date' => $date, ':start' => $start, ':end' => $end, ':notes' => $notes, ':id' => $id]
            ) > 0;
        } catch (PDOException $e) {
            throw new DomainException(db_error_message($e), 0, $e);
        }
    }

    /** Cambia el estado validando las transiciones permitidas. */
    public static function setStatus(int $id, string $status, ?string $reason = null): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainException('Estado de cita no válido.');
        }
        $current = self::find($id);
        if (!$current) {
            throw new DomainException('La cita no existe.');
        }

        $allowed = self::allowedTransitions($current['status']);
        if (!in_array($status, $allowed, true)) {
            throw new DomainException(sprintf(
                'No se puede pasar de "%s" a "%s".',
                $current['status'],
                $status
            ));
        }

        return self::run(
            'UPDATE appointments SET status = :status, cancellation_reason = :reason WHERE id = :id',
            [':status' => $status, ':reason' => $reason, ':id' => $id]
        ) > 0;
    }

    /** Transiciones permitidas desde cada estado. */
    public static function allowedTransitions(string $from): array
    {
        return match ($from) {
            'programada' => ['confirmada', 'atendida', 'cancelada', 'inasistente'],
            'confirmada' => ['atendida', 'cancelada', 'inasistente'],
            'atendida'   => [],
            'cancelada'  => ['programada'],   // reactivación (nueva fecha vía reprogramar)
            'inasistente'=> ['programada'],
            default      => [],
        };
    }

    /** Cancelación lógica con motivo. */
    public static function cancel(int $id, string $reason): bool
    {
        return self::setStatus($id, 'cancelada', $reason);
    }

    public static function delete(int $id): bool
    {
        return self::run('DELETE FROM appointments WHERE id = :id', [':id' => $id]) > 0;
    }

    /** Citas de hoy de un médico. */
    public static function forDoctorDate(int $doctorId, string $date): array
    {
        return self::rows(
            'SELECT * FROM v_appointments WHERE doctor_id = :doctor AND scheduled_date = :date
              ORDER BY start_time',
            [':doctor' => $doctorId, ':date' => $date]
        );
    }

    /** Próximas citas de un paciente. */
    public static function upcomingForPatient(int $patientId, int $limit = 5): array
    {
        return self::rows(
            "SELECT * FROM v_appointments
              WHERE patient_id = :patient
                AND scheduled_date >= CURRENT_DATE
                AND status IN ('programada', 'confirmada')
              ORDER BY scheduled_date, start_time
              LIMIT " . $limit,
            [':patient' => $patientId]
        );
    }

    /** Resumen por estado para dashboards y reportes. */
    public static function statusSummary(array $filters = []): array
    {
        [$where, $params] = self::filters($filters);
        $rows = self::rows(
            "SELECT a.status, COUNT(*)::int AS total
               FROM v_appointments a JOIN doctors d ON d.id = a.doctor_id
              WHERE {$where}
              GROUP BY a.status",
            $params
        );
        $summary = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $row) {
            $summary[$row['status']] = (int) $row['total'];
        }
        $summary['total'] = array_sum($summary);
        return $summary;
    }
}
