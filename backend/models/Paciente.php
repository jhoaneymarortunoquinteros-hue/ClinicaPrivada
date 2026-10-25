<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Pacientes + datos del historial clínico digital.
 */
final class Paciente extends Model
{
    private static function filters(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(lower(pv.full_name || ' ' || pv.document_number || ' ' ||
                                coalesce(pv.email, '')) LIKE :q)";
            $params[':q'] = '%' . mb_strtolower($q, 'UTF-8') . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    public static function list(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $params] = self::filters($filters);
        $sql = "SELECT * FROM v_patients pv WHERE {$where} ORDER BY pv.last_name, pv.first_name";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }
        return self::rows($sql, $params);
    }

    public static function count(array $filters = []): int
    {
        [$where, $params] = self::filters($filters);
        return (int) self::scalar("SELECT COUNT(*) FROM v_patients pv WHERE {$where}", $params);
    }

    public static function find(int $id): ?array
    {
        return self::row('SELECT * FROM v_patients WHERE id = :id', [':id' => $id]);
    }

    public static function findByUserId(int $userId): ?array
    {
        return self::row('SELECT * FROM v_patients WHERE user_id = :user_id', [':user_id' => $userId]);
    }

    public static function documentExists(string $document, int $excludeId = 0): bool
    {
        return (bool) self::scalar(
            'SELECT COUNT(*) FROM patients WHERE upper(document_number) = upper(:document) AND id <> :id',
            [':document' => $document, ':id' => $excludeId]
        );
    }

    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO patients (user_id, document_type, document_number, birth_date, gender, blood_type,
                                   address, emergency_contact_name, emergency_contact_phone, insurance,
                                   allergies, base_diseases, medical_background)
             VALUES (:user_id, :document_type, :document_number, :birth_date, :gender, :blood_type,
                     :address, :emergency_contact_name, :emergency_contact_phone, :insurance,
                     :allergies, :base_diseases, :medical_background)
             RETURNING id',
            [
                ':user_id'                 => (int) $data['user_id'],
                ':document_type'           => $data['document_type'] ?: 'DNI',
                ':document_number'         => trim((string) $data['document_number']),
                ':birth_date'              => $data['birth_date'] ?: null,
                ':gender'                  => $data['gender'] ?: null,
                ':blood_type'              => $data['blood_type'] ?: null,
                ':address'                 => $data['address'] ?? null,
                ':emergency_contact_name'  => $data['emergency_contact_name'] ?? null,
                ':emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                ':insurance'               => $data['insurance'] ?? null,
                ':allergies'               => trim((string) ($data['allergies'] ?? '')),
                ':base_diseases'           => trim((string) ($data['base_diseases'] ?? '')),
                ':medical_background'      => trim((string) ($data['medical_background'] ?? '')),
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return self::run(
            'UPDATE patients
                SET document_type = :document_type, document_number = :document_number,
                    birth_date = :birth_date, gender = :gender, blood_type = :blood_type,
                    address = :address,
                    emergency_contact_name = :emergency_contact_name,
                    emergency_contact_phone = :emergency_contact_phone,
                    insurance = :insurance,
                    allergies = :allergies, base_diseases = :base_diseases,
                    medical_background = :medical_background
              WHERE id = :id',
            [
                ':id'                      => $id,
                ':document_type'           => $data['document_type'] ?: 'DNI',
                ':document_number'         => trim((string) $data['document_number']),
                ':birth_date'              => $data['birth_date'] ?: null,
                ':gender'                  => $data['gender'] ?: null,
                ':blood_type'              => $data['blood_type'] ?: null,
                ':address'                 => $data['address'] ?? null,
                ':emergency_contact_name'  => $data['emergency_contact_name'] ?? null,
                ':emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                ':insurance'               => $data['insurance'] ?? null,
                ':allergies'               => trim((string) ($data['allergies'] ?? '')),
                ':base_diseases'           => trim((string) ($data['base_diseases'] ?? '')),
                ':medical_background'      => trim((string) ($data['medical_background'] ?? '')),
            ]
        ) > 0;
    }

    public static function total(): int
    {
        return (int) self::scalar('SELECT COUNT(*) FROM patients');
    }

    /** Pacientes con citas de un médico (agenda del médico). */
    public static function forDoctor(int $doctorId, string $q = ''): array
    {
        $params = [':doctor' => $doctorId];
        $search = '';
        if (trim($q) !== '') {
            $search = "AND lower(pv.full_name || ' ' || pv.document_number) LIKE :q";
            $params[':q'] = '%' . mb_strtolower(trim($q), 'UTF-8') . '%';
        }

        return self::rows(
            "SELECT pv.id, pv.document_number, pv.birth_date, pv.gender, pv.blood_type,
                    pv.allergies, pv.base_diseases, pv.full_name, pv.phone, pv.email,
                    COUNT(a.id)::int AS appointments,
                    MAX(a.scheduled_date) AS last_visit
               FROM v_patients pv
               JOIN appointments a ON a.patient_id = pv.id AND a.doctor_id = :doctor
              WHERE 1 = 1 {$search}
              GROUP BY pv.id, pv.full_name, pv.document_number, pv.birth_date, pv.gender,
                       pv.blood_type, pv.allergies, pv.base_diseases, pv.phone, pv.email
              ORDER BY last_visit DESC NULLS LAST, pv.full_name",
            $params
        );
    }
}
