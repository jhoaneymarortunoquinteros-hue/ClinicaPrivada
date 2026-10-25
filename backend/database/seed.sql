-- =====================================================================
--  Clínica VidaSalud - Datos iniciales de prueba
--  Ejecutar: psql -U postgres -d clinica -f database/seed.sql
--  (o php database/migrate.php, que además re-escribe las contraseñas
--   con password_hash() de PHP para garantizar el inicio de sesión)
-- =====================================================================

INSERT INTO roles (id, code, name, description)
VALUES
    (1, 'admin',         'Administrador',   'Gestión de usuarios, médicos, especialidades y reportes'),
    (2, 'recepcionista', 'Recepcionista',   'Registro de pacientes y gestión completa de citas'),
    (3, 'medico',        'Médico',          'Agenda propia, historiales clínicos y estados de turnos'),
    (4, 'paciente',      'Paciente',        'Autogestión de citas y su historial clínico digital')
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('roles', 'id'), (SELECT MAX(id) FROM roles));

-- ---------------------------------------------------------------------
-- ESPECIALIDADES (duración de la consulta en minutos)
-- ---------------------------------------------------------------------
INSERT INTO specialties (id, name, duration_minutes, description) VALUES
    (1, 'Medicina General',        15, 'Atención primaria, prevención y seguimiento general'),
    (2, 'Cardiología',             30, 'Enfermedades del sistema cardiovascular'),
    (3, 'Pediatría',               20, 'Salud integral de niños y adolescentes'),
    (4, 'Dermatología',            20, 'Enfermedades de la piel, cabello y uñas'),
    (5, 'Ginecología',             30, 'Salud reproductiva de la mujer'),
    (6, 'Traumatología',           25, 'Huesos, articulaciones y sistema musculoesquelético'),
    (7, 'Odontología',             30, 'Salud bucal y dental'),
    (8, 'Psicología Clínica',      45, 'Salud mental y acompañamiento terapéutico')
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('specialties', 'id'), (SELECT MAX(id) FROM specialties));

-- ---------------------------------------------------------------------
-- USUARIOS DE PRUEBA (contraseñas provisoria con bcrypt de pgcrypto;
-- migrate.php las reemplaza por hashes generados con password_hash)
-- ---------------------------------------------------------------------
INSERT INTO users (id, role_id, username, email, password_hash, first_name, last_name, phone, active) VALUES
    (1, 1, 'admin',       'admin@clinica.test',    crypt('Vida#Salud2026!',   gen_salt('bf', 10)), 'Ana',      'Torres',  '3001112233', TRUE),
    (2, 2, 'recepcion',   'recepcion@clinica.test',crypt('Agenda$Segura26',   gen_salt('bf', 10)), 'Carlos',   'Mena',    '3002223344', TRUE),
    (3, 3, 'cardiologo',  'cardiologo@clinica.test',crypt('Consulta!Medica26',gen_salt('bf', 10)), 'Laura',    'Gómez',   '3003334455', TRUE),
    (4, 3, 'general',     'general@clinica.test',  crypt('Consulta!Medica26', gen_salt('bf', 10)), 'Jorge',    'Ruiz',    '3004445566', TRUE),
    (5, 3, 'pediatra',    'pediatra@clinica.test', crypt('Consulta!Medica26', gen_salt('bf', 10)), 'Sofía',    'Castillo','3005556677', TRUE),
    (6, 4, 'maria',       'maria@paciente.test',   crypt('Cita#Privada26',    gen_salt('bf', 10)), 'María',    'López',   '3101112233', TRUE),
    (7, 4, 'pedro',       'pedro@paciente.test',   crypt('Cita#Privada26',    gen_salt('bf', 10)), 'Pedro',    'Sánchez', '3102223344', TRUE),
    (8, 4, 'lucia',       'lucia@paciente.test',   crypt('Cita#Privada26',    gen_salt('bf', 10)), 'Lucía',    'Fernández','3103334455', TRUE)
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users));

-- ---------------------------------------------------------------------
-- MÉDICOS
-- ---------------------------------------------------------------------
INSERT INTO doctors (id, user_id, specialty_id, license_number, professional_title, bio, active) VALUES
    (1, 3, 2, 'MED-1001', 'Cardióloga',    'Especialista en cardiología clínica y electrocardiografía.', TRUE),
    (2, 4, 1, 'MED-1002', 'Médico general','Médico de familia con enfoque en prevención.',              TRUE),
    (3, 5, 3, 'MED-1003', 'Pediatra',      'Pediatra con experiencia en control de crecimiento.',        TRUE)
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('doctors', 'id'), (SELECT MAX(id) FROM doctors));

-- ---------------------------------------------------------------------
-- HORARIOS LABORALES (0=Dom, 1=Lun ... 6=Sáb)
-- ---------------------------------------------------------------------
INSERT INTO doctor_schedules (doctor_id, weekday, start_time, end_time, is_active)
SELECT d.id, dow.n, h.start_time, h.end_time, TRUE
FROM doctors d
CROSS JOIN (VALUES (1),(2),(3),(4),(5)) AS dow(n)      -- Lunes a Viernes
CROSS JOIN (VALUES (CAST('08:00' AS TIME), CAST('12:00' AS TIME)),
                  (CAST('14:00' AS TIME), CAST('18:00' AS TIME))) AS h(start_time, end_time)
ON CONFLICT (doctor_id, weekday, start_time) DO NOTHING;

-- La Dra. Gómez también atiende los sábados por la mañana
INSERT INTO doctor_schedules (doctor_id, weekday, start_time, end_time, is_active)
VALUES (1, 6, '08:00', '12:00', TRUE)
ON CONFLICT (doctor_id, weekday, start_time) DO NOTHING;

-- ---------------------------------------------------------------------
-- PACIENTES + HISTORIAL CLÍNICO DIGITAL
-- ---------------------------------------------------------------------
INSERT INTO patients (id, user_id, document_type, document_number, birth_date, gender, blood_type,
                      address, emergency_contact_name, emergency_contact_phone, insurance,
                      allergies, base_diseases, medical_background) VALUES
    (1, 6, 'DNI', '1020304050', '1988-04-12', 'F', 'O+',
     'Calle 12 #34-56, Bogotá', 'Luis López', '3109998877', 'SaludTotal',
     'Penicilina, mariscos', 'Hipertensión arterial leve',
     'Cirugía de apendicitis en 2015. Padre con diabetes tipo 2.'),
    (2, 7, 'DNI', '1030405060', '1975-09-30', 'M', 'A+',
     'Carrera 45 #67-89, Bogotá', 'Marta Sánchez', '3108887766', 'Sura',
     'Ninguna conocida', 'Diabetes tipo 2, colesterol alto',
     'Infarto agudo de miocardio en 2020. Fumador activo.'),
    (3, 8, 'CC',  '1040506070', '2015-01-22', 'F', 'B-',
     'Av. Siempre Viva 742, Bogotá', 'Rosario Fernández', '3107776655', 'MediPlus',
     'Alergia al maní (riesgo anafiláctico)', 'Asma intermitente',
     'Prematura a las 34 semanas. Vacunas al día.');

-- Reiniciar la secuencia de patients (se insertaron IDs explícitos arriba).
SELECT setval(pg_get_serial_sequence('patients', 'id'), (SELECT MAX(id) FROM patients));

-- ---------------------------------------------------------------------
-- FUNCIONIÓN AUXILIAR: próxima fecha con un día de la semana dado
--     seed_dow(1, 0) = próximo lunes (o hoy si es lunes)
--     seed_dow(2, -1) = último martes
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION seed_dow(target_dow INT, weeks_offset INT DEFAULT 0)
RETURNS DATE LANGUAGE sql AS $$
    SELECT (CURRENT_DATE
            + ((target_dow - CAST(EXTRACT(DOW FROM CURRENT_DATE) AS INT) + 7) % 7)
            + weeks_offset * 7)::DATE;
$$;

-- ---------------------------------------------------------------------
-- CITAS DE PRUEBA (pasadas atendidas + próximas programadas)
-- ---------------------------------------------------------------------
INSERT INTO appointments (patient_id, doctor_id, scheduled_date, start_time, end_time, status, reason, notes, cancellation_reason, created_by) VALUES
    -- Historial (semana pasada)
    (1, 2, seed_dow(1, -1), '10:00', '10:15', 'atendida',
     'Dolor de cabeza recurrente', 'Control de presión arterial.', NULL, 2),
    (2, 1, seed_dow(2, -1), '08:00', '08:30', 'atendida',
     'Dolor de pecho al esfuerzo', 'Se solicitaron estudios.', NULL, 2),
    (3, 3, seed_dow(3, -1), '11:00', '11:20', 'inasistente',
     'Control pediátrico anual', NULL, NULL, 2),
    (2, 2, seed_dow(4, -1), '08:00', '08:15', 'cancelada',
     'Control de diabetes', NULL, 'El paciente se encontraba indisponible.', 2),
    -- Agenda próxima
    (1, 1, seed_dow(1, 0), '08:00', '08:30', 'programada',
     'Seguimiento de hipertensión', NULL, NULL, 2),
    (2, 2, seed_dow(2, 0), '09:00', '09:15', 'confirmada',
     'Control de glucosa', NULL, NULL, 2),
    (3, 3, seed_dow(3, 0), '10:00', '10:20', 'programada',
     'Tos persistente', NULL, NULL, 2);

-- ---------------------------------------------------------------------
-- EVOLUCIÓN MÉDICA DE LAS CONSULTAS ATENDIDAS
-- ---------------------------------------------------------------------
INSERT INTO medical_records (patient_id, doctor_id, appointment_id, consultation_date,
                             chief_complaint, diagnosis, treatment, evolution, follow_up_date)
SELECT a.patient_id, a.doctor_id, a.id, a.scheduled_date,
       a.reason,
       CASE a.patient_id
           WHEN 1 THEN 'Cefalea tensional, control de PA dentro de rangos normales.'
           WHEN 2 THEN 'Angina estable, se descarta infarto agudo.'
           ELSE 'Cuadro respiratorio leve, sin signos de alarma.'
       END,
       CASE a.patient_id
           WHEN 1 THEN 'Analgesia oral y control de PA cada 12 horas.'
           WHEN 2 THEN 'Antiagregante, estatinas y cambios en estilo de vida.'
           ELSE 'Hidratación, nebulizaciones si lo requiere y control en 7 días.'
       END,
       CASE a.patient_id
           WHEN 1 THEN 'Paciente refiere mejoría. Se mantiene el mismo esquema.'
           WHEN 2 THEN 'Se ajusta medicación. Se solicita ECG de esfuerzo.'
           ELSE 'Familia refiere disminución de la tos.'
       END,
       a.scheduled_date + 14
FROM appointments a
WHERE a.status = 'atendida';

DROP FUNCTION seed_dow(INT, INT);

-- Datos listos: los usuarios y contraseñas válidas se completan con
--   php database/migrate.php
