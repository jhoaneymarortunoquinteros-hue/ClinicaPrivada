# Clínica VidaSalud — Sistema de Gestión de Citas Médicas

Sistema web **PHP + PostgreSQL** con arquitectura MVC ligera, autenticación por sesiones,
**4 roles** (Administrador, Recepcionista, Médico y Paciente), historial clínico digital,
control estricto de horarios/traslapes y reportes administrativos.

Diseño **fully responsive** con Tailwind CSS (mobile-first: barra lateral colapsable en móvil).

---

## 1. Estructura del proyecto

```
/
├── config/
│   ├── config.php          # Configuración general (app, BD, sesión) + variables de entorno
│   ├── database.php        # Conexión PDO singleton a PostgreSQL
│   └── env.local.php       # (opcional) Overrides locales de configuración
├── core/
│   ├── bootstrap.php       # Autoload, sesión, CSRF
│   ├── Auth.php            # Login/logout + control de acceso por roles
│   ├── Router.php          # Enrutador: index.php?r=controlador/acción
│   └── helpers.php         # url(), view(), e(), flash(), validación, iconos, fechas...
├── controllers/            # Lógica de entrada (10 controladores)
│   ├── AuthController.php          DashboardController.php   UsuarioController.php
│   ├── EspecialidadController.php  MedicoController.php      PacienteController.php
│   ├── CitaController.php          HorarioController.php     HistorialController.php
│   └── ReporteController.php
├── models/                 # Acceso a datos y reglas de negocio (consultas preparadas)
│   ├── Model.php  Usuario.php  Especialidad.php  Medico.php  Paciente.php
│   └── Cita.php  Horario.php  Historial.php  Reporte.php
├── views/
│   ├── layouts/            # app.php (autenticado) · auth.php (público)
│   ├── auth/               # login · register
│   ├── shared/             # error · appointment-form · pagination · cancel-form
│   ├── admin/              # Panel, usuarios, médicos, especialidades, reportes
│   ├── receptionist/       # Panel, pacientes, formulario de paciente, citas
│   ├── doctor/             # Panel, agenda, horarios, pacientes, historial
│   └── patient/            # Panel, citas, historial, perfil
├── database/
│   ├── schema.sql          # Tablas, FKs, índices, triggers (validación) y vistas
│   ├── seed.sql            # Datos de prueba (roles, especialidades, médicos, citas...)
│   └── migrate.php         # Migrador CLI: schema + seed + contraseñas
├── public/                 # DocumentRoot
│   ├── index.php           # Punto de entrada único
│   └── assets/ css · js
├── Dockerfile · docker-compose.yml
└── README.md
```

---

## 2. Puesta en marcha

### Opción A — Docker (recomendada)

```bash
docker compose up -d --build
# App:  http://localhost:8080
# BD:   postgresql://clinica:clinica@db:5432/clinica   (red interna del compose)
# BD desde el host: localhost:5433 (evita conflictos con otra PostgreSQL local)
```

El contenedor `migrate` ejecuta `database/schema.sql` + `seed.sql` y establece las
contraseñas de prueba antes de levantar la app.

### Opción B — Local (XAMPP / Apache + PostgreSQL)

1. Crea la base de datos:
   ```sql
   CREATE DATABASE clinica;
   ```
2. Configura las credenciales en `config/config.php` (o variables `DB_*`).
3. Ejecuta la migración:
   ```bash
   php database/migrate.php          # limpia y reconstruye: --fresh
   ```
4. Apunta Apache a la carpeta `public/` (DocumentRoot) o usa:
   ```bash
   php -S localhost:8080 -t public
   ```

### Opción C — Solo SQL (nube: Neon, Supabase, RDS, Aiven...)

```bash
psql "postgres://user:pass@host:5432/dbname" -f database/schema.sql
psql "postgres://user:pass@host:5432/dbname" -f database/seed.sql
```
> `seed.sql` usa la extensión `pgcrypto` (disponible en todos los servicios gestionados)
> para generar hashes bcrypt. Si no la tienes, ejecuta `php database/migrate.php`,
> que reescribe las contraseñas con `password_hash()` de PHP.

---

## 3. Cuentas de prueba

El inicio de sesión se hace con el **nombre de usuario** (no con el correo).

| Rol            | Nombre de usuario    | Contraseña          |
|----------------|----------------------|---------------------|
| Administrador  | `admin`              | `Vida#Salud2026!`   |
| Recepcionista  | `recepcion`          | `Agenda$Segura26`   |
| Médico         | `cardiologo`         | `Consulta!Medica26` |
| Médico         | `general`            | `Consulta!Medica26` |
| Médico         | `pediatra`           | `Consulta!Medica26` |
| Paciente       | `maria`              | `Cita#Privada26`    |
| Paciente       | `pedro`              | `Cita#Privada26`    |
| Paciente       | `lucia`              | `Cita#Privada26`    |

Todas cumplen la política de contraseñas: **mínimo 8 caracteres, con mayúscula,
minúscula, número y un carácter especial**.

---

## 4. Roles y alcance

| Rol | Capacidades |
|-----|-------------|
| **Administrador** | Usuarios y roles, registro de médicos + especialidades, activar/desactivar cuentas, reportes con filtros y exportación CSV. |
| **Recepcionista** | Registro/búsqueda de pacientes, programar, reprogramar y cancelar citas, registrar asistencia/inasistencia, consultar médicos y horarios. |
| **Médico** | Ver su agenda por fecha, confirmar/atender turnos, marcar inasistencias, consultar historiales de sus pacientes y registrar evolución médica, gestionar su horario laboral. |
| **Paciente** | Auto-registro, editar sus datos personales, solicitar y cancelar sus citas, ver su historial clínico digital (antecedentes, tipo de sangre, alergias, enfermedades de base y evoluciones). |

---

## 5. Reglas de negocio clave

1. **Duración por especialidad** — cada cita debe durar exactamente los minutos de la
   especialidad (Medicina General 15 min, Cardiología 30 min, Pediatría 20 min...).
2. **Horario laboral** — solo se reservan días/horas presentes en `doctor_schedules`
   (día sin bloque = día no disponible).
3. **Sin traslapes** — solapamientos prohibidos para el mismo médico en la misma fecha.
4. **Sin fechas pasadas** al crear una cita.
5. **Transiciones de estado** controladas: `programada → confirmada → atendida`,
   con `cancelada` e `inasistente` como salidas válidas.

> Estas reglas se validan **dos veces**: en PHP (mensajes amigables) y en el trigger
> `fn_appointments_validate` de PostgreSQL (garantía de integridad aunque se inserte
> desde otra herramienta).

---

## 6. Seguridad

- Contraseñas con `password_hash()` / BCRYPT.
- Consultas 100 % preparadas (PDO) contra inyección SQL.
- Escape de salida con `e()` (htmlspecialchars) contra XSS.
- Token **CSRF** validado en toda petición POST.
- Sesiones con cookies `HttpOnly` + `SameSite=Lax` y regeneración de ID en el login.
- Control de acceso por rol en cada controlador (`Auth::requireRole`).
- Rate-limit básico (pausa) ante fallos de login.

---

## 7. Rutas principales

El enrutado es `index.php?r=controlador/acción`, por ejemplo:

| Ruta | Descripción |
|------|-------------|
| `dashboard/index` | Panel según rol |
| `auth/login` · `auth/register` · `auth/logout` | Sesión |
| `usuarios/index` · `usuarios/guardar` · `usuarios/toggle` | Usuarios (admin) |
| `medicos/index` · `medicos/guardar` | Médicos (admin) |
| `especialidades/index` | Especialidades (admin) |
| `pacientes/index` · `pacientes/crear` · `pacientes/editar` · `pacientes/guardar` | Pacientes |
| `pacientes/perfil` · `pacientes/guardarPerfil` | Perfil del paciente |
| `citas/index` · `citas/crear` · `citas/guardar` · `citas/reprogramar` · `citas/cancelar` · `citas/estado` | Citas |
| `citas/disponibilidad` | API JSON de franjas libres |
| `horarios/index` · `horarios/guardar` | Horarios laborales |
| `historial/pacientes` · `historial/ver` · `historial/guardar` · `historial/miHistorial` | Historial clínico |
| `reportes/index` · `reportes/csv` | Reportes (admin) |

---

## 8. Producción

El `index.php` actual usa Tailwind **Play CDN** para cero configuración. En producción
compila Tailwind:

```bash
npm i -D tailwindcss @tailwindcss/forms
npx tailwindcss -i resources/css/app.css -o public/assets/css/tailwind.css --minify
```

y reemplaza el `<script src="https://cdn.tailwindcss.com">` por la hoja compilada.
