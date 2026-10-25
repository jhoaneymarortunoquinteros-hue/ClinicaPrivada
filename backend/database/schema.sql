-- =====================================================================
--  Clínica VidaSalud - Esquema PostgreSQL
--  Ejecutar:  psql -U postgres -d clinica -f database/schema.sql
--  o con el migrador:  php database/migrate.php [--fresh]
-- =====================================================================

-- Extensiones útiles (disponibles en la mayoría de servicios cloud)
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- ---------------------------------------------------------------------
-- 1. ROLES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          SMALLSERIAL PRIMARY KEY,
    code        VARCHAR(20)  UNIQUE NOT NULL,
    name        VARCHAR(50)  NOT NULL,
    description TEXT
);

-- ---------------------------------------------------------------------
-- 2. USUARIOS (base de autenticación para los 4 roles)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id             BIGSERIAL PRIMARY KEY,
    role_id        SMALLINT     NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
    -- Nombre de usuario: es lo que se usa para iniciar sesión (auto-registro).
    username       VARCHAR(40),
    -- Correo de contacto: obligatorio para cuentas creadas por el personal,
    -- opcional en el auto-registro (allí se pide el nombre de usuario).
    email          VARCHAR(120) UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    first_name     VARCHAR(80)  NOT NULL,
    last_name      VARCHAR(80)  NOT NULL,
    phone          VARCHAR(30),
    active         BOOLEAN      NOT NULL DEFAULT TRUE,
    last_login_at  TIMESTAMPTZ,
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role_id);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(active);
-- Unicidad del nombre de usuario sin distinguir mayúsculas/minúsculas.
-- Se protege con una comprobación: en bases de datos creadas antes de
-- existir la columna "username", migrate.php la añade después.
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'username'
    ) THEN
        CREATE UNIQUE INDEX IF NOT EXISTS uq_users_username
            ON users (lower(username)) WHERE username IS NOT NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 3. ESPECIALIDADES (definen la duración de cada consulta)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS specialties (
    id               SMALLSERIAL PRIMARY KEY,
    name             VARCHAR(80) UNIQUE NOT NULL,
    duration_minutes SMALLINT    NOT NULL DEFAULT 15
                     CHECK (duration_minutes BETWEEN 5 AND 240),
    description      TEXT,
    active           BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- 4. MÉDICOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctors (
    id                 BIGSERIAL PRIMARY KEY,
    user_id            BIGINT      UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    specialty_id       SMALLINT    NOT NULL REFERENCES specialties(id) ON DELETE RESTRICT,
    license_number     VARCHAR(40) UNIQUE NOT NULL,
    professional_title VARCHAR(120),
    bio                TEXT,
    active             BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_doctors_specialty ON doctors(specialty_id);

-- ---------------------------------------------------------------------
-- 5. HORARIOS LABORALES SEMANALES DEL MÉDICO
--    Día sin registro = día no disponible para reservas.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctor_schedules (
    id         BIGSERIAL PRIMARY KEY,
    doctor_id  BIGINT  NOT NULL REFERENCES doctors(id) ON DELETE CASCADE,
    weekday    SMALLINT NOT NULL CHECK (weekday BETWEEN 0 AND 6), -- 0=Domingo .. 6=Sábado
    start_time TIME    NOT NULL,
    end_time   TIME    NOT NULL,
    is_active  BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CHECK (start_time < end_time),
    UNIQUE (doctor_id, weekday, start_time)
);
CREATE INDEX IF NOT EXISTS idx_schedules_doctor ON doctor_schedules(doctor_id, weekday);

-- ---------------------------------------------------------------------
-- 6. PACIENTES + HISTORIAL CLÍNICO DIGITAL (antecedentes, alergias...)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id                      BIGSERIAL PRIMARY KEY,
    user_id                 BIGINT      UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    document_type           VARCHAR(10) NOT NULL DEFAULT 'DNI',
    document_number         VARCHAR(30) UNIQUE NOT NULL,
    birth_date              DATE,
    gender                  CHAR(1)     CHECK (gender IN ('F', 'M', 'O')),
    blood_type              VARCHAR(3)  CHECK (blood_type IN ('A+','A-','B+','B-','AB+','AB-','O+','O-')),
    address                 TEXT,
    emergency_contact_name  VARCHAR(120),
    emergency_contact_phone VARCHAR(30),
    insurance               VARCHAR(80),
    allergies               TEXT        NOT NULL DEFAULT '',
    base_diseases           TEXT        NOT NULL DEFAULT '',
    medical_background      TEXT        NOT NULL DEFAULT '',
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_patients_document ON patients(document_number);

-- ---------------------------------------------------------------------
-- 7. CITAS MÉDICAS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id                  BIGSERIAL PRIMARY KEY,
    patient_id          BIGINT      NOT NULL REFERENCES patients(id) ON DELETE RESTRICT,
    doctor_id           BIGINT      NOT NULL REFERENCES doctors(id)  ON DELETE RESTRICT,
    scheduled_date      DATE        NOT NULL,
    start_time          TIME        NOT NULL,
    end_time            TIME        NOT NULL,
    status              VARCHAR(20) NOT NULL DEFAULT 'programada'
                        CHECK (status IN ('programada','confirmada','atendida','cancelada','inasistente')),
    reason              TEXT,
    notes               TEXT,
    cancellation_reason TEXT,
    created_by          BIGINT      REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CHECK (start_time < end_time)
);
CREATE INDEX IF NOT EXISTS idx_appointments_doctor_date ON appointments(doctor_id, scheduled_date);
CREATE INDEX IF NOT EXISTS idx_appointments_patient_date ON appointments(patient_id, scheduled_date);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status);

-- ---------------------------------------------------------------------
-- 8. EVOLUCIÓN MÉDICA POR CONSULTA (historial clínico ampliado)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medical_records (
    id               BIGSERIAL PRIMARY KEY,
    patient_id       BIGINT  NOT NULL REFERENCES patients(id)     ON DELETE CASCADE,
    doctor_id        BIGINT  NOT NULL REFERENCES doctors(id)      ON DELETE RESTRICT,
    appointment_id   BIGINT  REFERENCES appointments(id)          ON DELETE SET NULL,
    consultation_date DATE    NOT NULL DEFAULT CURRENT_DATE,
    chief_complaint  TEXT,
    diagnosis        TEXT,
    treatment        TEXT,
    evolution        TEXT,
    follow_up_date   DATE,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_records_patient ON medical_records(patient_id, consultation_date);

-- =====================================================================
-- TRIGGERS: VALIDACIÓN ESTRICTA DE CITAS (duración, horario, traslapes)
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_appointments_validate() RETURNS TRIGGER AS $$
DECLARE
    v_duration  SMALLINT;
    v_active    BOOLEAN;
    v_minutes   INT;
    v_schedule  RECORD;
BEGIN
    IF NEW.status = 'cancelada' THEN
        RETURN NEW; -- las cancelaciones nunca bloquean validaciones
    END IF;

    SELECT sp.duration_minutes, d.active
      INTO v_duration, v_active
      FROM doctors d
      JOIN specialties sp ON sp.id = d.specialty_id
     WHERE d.id = NEW.doctor_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'El médico seleccionado no existe';
    END IF;
    IF NOT v_active THEN
        RAISE EXCEPTION 'El médico seleccionado está inactivo';
    END IF;
    IF NEW.end_time <= NEW.start_time THEN
        RAISE EXCEPTION 'La hora de fin debe ser posterior a la hora de inicio';
    END IF;

    -- Duración exacta según la especialidad
    v_minutes := CAST(EXTRACT(EPOCH FROM (NEW.end_time - NEW.start_time)) / 60 AS INT);
    IF v_minutes <> v_duration THEN
        RAISE EXCEPTION 'La duración de la cita debe ser de % minutos (duración de la especialidad)', v_duration;
    END IF;

    -- Día y horario laboral del médico
    SELECT ds.start_time, ds.end_time
      INTO v_schedule
      FROM doctor_schedules ds
     WHERE ds.doctor_id = NEW.doctor_id
       AND ds.weekday = CAST(EXTRACT(DOW FROM NEW.scheduled_date) AS SMALLINT)
       AND ds.is_active
       AND ds.start_time <= NEW.start_time
       AND ds.end_time   >= NEW.end_time
     LIMIT 1;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'La cita queda fuera del horario laboral del médico (día u hora no disponible)';
    END IF;

    -- Sin traslapes para el mismo médico (fecha y horario)
    IF EXISTS (
        SELECT 1
          FROM appointments a
         WHERE a.doctor_id = NEW.doctor_id
           AND a.scheduled_date = NEW.scheduled_date
           AND a.status <> 'cancelada'
           AND a.start_time < NEW.end_time
           AND a.end_time   > NEW.start_time
           AND (TG_OP = 'INSERT' OR a.id <> NEW.id)
    ) THEN
        RAISE EXCEPTION 'El médico ya tiene una cita que se traslapa en esa fecha y horario';
    END IF;

    -- No agendar en fechas pasadas (solo aplica a citas vigentes;
    -- permite importar historial atendido/inasistente)
    IF TG_OP = 'INSERT'
       AND NEW.scheduled_date < CURRENT_DATE
       AND NEW.status IN ('programada', 'confirmada') THEN
        RAISE EXCEPTION 'No se pueden crear citas en fechas pasadas';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tr_appointments_validate ON appointments;
CREATE TRIGGER tr_appointments_validate
BEFORE INSERT OR UPDATE ON appointments
FOR EACH ROW EXECUTE FUNCTION fn_appointments_validate();

-- updated_at automático
CREATE OR REPLACE FUNCTION fn_touch_updated_at() RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tr_users_touch ON users;
CREATE TRIGGER tr_users_touch BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();

DROP TRIGGER IF EXISTS tr_doctors_touch ON doctors;
CREATE TRIGGER tr_doctors_touch BEFORE UPDATE ON doctors
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();

DROP TRIGGER IF EXISTS tr_patients_touch ON patients;
CREATE TRIGGER tr_patients_touch BEFORE UPDATE ON patients
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();

DROP TRIGGER IF EXISTS tr_appointments_touch ON appointments;
CREATE TRIGGER tr_appointments_touch BEFORE UPDATE ON appointments
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();

-- =====================================================================
-- VISTAS PARA CONSULTAS COMUNES
-- Se eliminan antes de recrearlas: CREATE OR REPLACE no permite cambiar
-- la estructura de columnas y users ahora incluye "username".
-- =====================================================================
DROP VIEW IF EXISTS v_users            CASCADE;
DROP VIEW IF EXISTS v_patients         CASCADE;
DROP VIEW IF EXISTS v_doctors          CASCADE;
DROP VIEW IF EXISTS v_appointments     CASCADE;
DROP VIEW IF EXISTS v_medical_records  CASCADE;

CREATE OR REPLACE VIEW v_users AS
SELECT u.*, r.code AS role_code, r.name AS role_name
FROM users u
JOIN roles r ON r.id = u.role_id;

CREATE OR REPLACE VIEW v_patients AS
SELECT p.*, u.username, u.email, u.first_name, u.last_name, u.phone, u.active,
       (u.first_name || ' ' || u.last_name) AS full_name
FROM patients p
JOIN users u ON u.id = p.user_id;

CREATE OR REPLACE VIEW v_doctors AS
SELECT d.*, sp.name AS specialty_name, sp.duration_minutes,
       u.username, u.email, u.first_name, u.last_name, u.phone, u.active AS user_active,
       (u.first_name || ' ' || u.last_name) AS full_name
FROM doctors d
JOIN specialties sp ON sp.id = d.specialty_id
JOIN users u ON u.id = d.user_id;

CREATE OR REPLACE VIEW v_appointments AS
SELECT a.*,
       p.document_number AS patient_document,
       p.blood_type      AS patient_blood_type,
       (pu.first_name || ' ' || pu.last_name) AS patient_name,
       pu.email          AS patient_email,
       (du.first_name || ' ' || du.last_name) AS doctor_name,
       du.email          AS doctor_email,
       d.specialty_id    AS specialty_id,
       sp.name           AS specialty_name,
       sp.duration_minutes
FROM appointments a
JOIN patients p       ON p.id = a.patient_id
JOIN users pu         ON pu.id = p.user_id
JOIN doctors d        ON d.id = a.doctor_id
JOIN users du         ON du.id = d.user_id
JOIN specialties sp   ON sp.id = d.specialty_id;

CREATE OR REPLACE VIEW v_medical_records AS
SELECT mr.*,
       (pu.first_name || ' ' || pu.last_name) AS patient_name,
       (du.first_name || ' ' || du.last_name) AS doctor_name,
       sp.name AS specialty_name
FROM medical_records mr
JOIN patients p        ON p.id = mr.patient_id
JOIN users pu          ON pu.id = p.user_id
JOIN doctors d         ON d.id = mr.doctor_id
JOIN users du          ON du.id = d.user_id
JOIN specialties sp    ON sp.id = d.specialty_id;
