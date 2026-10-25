<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Horario laboral semanal del médico.
 * weekday: 0=Domingo, 1=Lunes ... 6=Sábado
 */
final class Horario extends Model
{
    public static array $days = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public static function forDoctor(int $doctorId): array
    {
        return self::rows(
            'SELECT * FROM doctor_schedules WHERE doctor_id = :id ORDER BY weekday, start_time',
            [':id' => $doctorId]
        );
    }

    public static function find(int $id): ?array
    {
        return self::row('SELECT * FROM doctor_schedules WHERE id = :id', [':id' => $id]);
    }

    /** ¿Existe un bloque horario que contenga start/end en ese día de la semana? */
    public static function covers(int $doctorId, int $weekday, string $start, string $end): bool
    {
        return (bool) self::scalar(
            'SELECT COUNT(*) FROM doctor_schedules
              WHERE doctor_id = :doctor AND weekday = :weekday AND is_active = TRUE
                AND start_time <= :start AND end_time >= :end',
            [':doctor' => $doctorId, ':weekday' => $weekday, ':start' => $start, ':end' => $end]
        );
    }

    /** ¿El bloque nuevo se traslapa con otro ya existente del mismo día? */
    public static function hasConflict(int $doctorId, int $weekday, string $start, string $end, int $excludeId = 0): bool
    {
        return (bool) self::scalar(
            'SELECT COUNT(*) FROM doctor_schedules
              WHERE doctor_id = :doctor AND weekday = :weekday AND id <> :id
                AND start_time < :end AND end_time > :start',
            [
                ':doctor'  => $doctorId,
                ':weekday' => $weekday,
                ':start'   => $start,
                ':end'     => $end,
                ':id'      => $excludeId,
            ]
        );
    }

    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO doctor_schedules (doctor_id, weekday, start_time, end_time, is_active)
             VALUES (:doctor_id, :weekday, :start_time, :end_time, TRUE) RETURNING id',
            [
                ':doctor_id'  => (int) $data['doctor_id'],
                ':weekday'    => (int) $data['weekday'],
                ':start_time' => $data['start_time'],
                ':end_time'   => $data['end_time'],
            ]
        );
    }

    public static function destroy(int $id): bool
    {
        return self::run('DELETE FROM doctor_schedules WHERE id = :id', [':id' => $id]) > 0;
    }

    public static function toggle(int $id): bool
    {
        return self::run(
            'UPDATE doctor_schedules SET is_active = NOT is_active WHERE id = :id',
            [':id' => $id]
        ) > 0;
    }

    /** Agenda semanal consolidada para mostrar en la vista. */
    public static function weekly(int $doctorId): array
    {
        $rows = self::forDoctor($doctorId);
        $week = [];
        foreach (self::$days as $num => $label) {
            $week[$num] = ['label' => $label, 'blocks' => []];
        }
        foreach ($rows as $row) {
            $week[(int) $row['weekday']]['blocks'][] = $row;
        }
        return $week;
    }
}
