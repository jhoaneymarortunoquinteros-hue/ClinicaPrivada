<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Historial clínico digital: evolución médica por consulta.
 */
final class Historial extends Model
{
    /** Evolución de un paciente (con datos del médico que atendió). */
    public static function forPatient(int $patientId, int $limit = 0, int $offset = 0): array
    {
        $sql = 'SELECT * FROM v_medical_records WHERE patient_id = :patient
                 ORDER BY consultation_date DESC, created_at DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }
        return self::rows($sql, [':patient' => $patientId]);
    }

    public static function countForPatient(int $patientId): int
    {
        return (int) self::scalar(
            'SELECT COUNT(*) FROM medical_records WHERE patient_id = :patient',
            [':patient' => $patientId]
        );
    }

    public static function find(int $id): ?array
    {
        return self::row('SELECT * FROM v_medical_records WHERE id = :id', [':id' => $id]);
    }

    /** Registra la evolución de una consulta. */
    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO medical_records
                 (patient_id, doctor_id, appointment_id, consultation_date,
                  chief_complaint, diagnosis, treatment, evolution, follow_up_date)
             VALUES
                 (:patient_id, :doctor_id, :appointment_id, :consultation_date,
                  :chief_complaint, :diagnosis, :treatment, :evolution, :follow_up_date)
             RETURNING id',
            [
                ':patient_id'        => (int) $data['patient_id'],
                ':doctor_id'         => (int) $data['doctor_id'],
                ':appointment_id'    => $data['appointment_id'] ?: null,
                ':consultation_date' => $data['consultation_date'] ?: date('Y-m-d'),
                ':chief_complaint'   => $data['chief_complaint'] ?? null,
                ':diagnosis'         => $data['diagnosis'] ?? null,
                ':treatment'         => $data['treatment'] ?? null,
                ':evolution'         => $data['evolution'] ?? null,
                ':follow_up_date'    => $data['follow_up_date'] ?: null,
            ]
        );
    }

    /** Última evolución registrada (para el resumen del paciente). */
    public static function latestForPatient(int $patientId): ?array
    {
        return self::row(
            'SELECT * FROM v_medical_records WHERE patient_id = :patient
              ORDER BY consultation_date DESC, created_at DESC LIMIT 1',
            [':patient' => $patientId]
        );
    }
}
