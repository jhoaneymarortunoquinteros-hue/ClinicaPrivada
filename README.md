# Clínica VidaSalud — Sistema de Gestión de Citas Médicas

Sistema web **PHP + PostgreSQL** con arquitectura MVC ligera, autenticación por sesiones,
**4 roles** (Administrador, Recepcionista, Médico y Paciente), historial clínico digital,
control estricto de horarios/traslapes y reportes administrativos.

Diseño **fully responsive** con Tailwind CSS (mobile-first: barra lateral colapsable en móvil).

---

## 1. Estructura del proyecto

Todo lo del **backend** está en `backend/` y todo lo del **frontend** en `frontend/`,
de modo que la raíz queda limpia:

```
/
├── backend/                    # BACKEND (nunca es público)
│   ├── config/
│   │   ├── config.php          # Configuración general (app, BD, sesión) + variables de entorno
│   │   ├── database.php        # Conexión PDO singleton a PostgreSQL
│   │   └── env.local.php       # (opcional) Overrides locales de configuración
│   ├── core/
│   │   ├── bootstrap.php       # Autoload, sesión, CSRF
│   │   ├── Auth.php            # Login/logout + control de acceso por roles
│   │   ├── Router.php          # Enrutador: index.php?r=controlador/acción
│   │   └── helpers.php         # url(), view(), e(), flash(), validación, iconos, fechas...
│   ├── controllers/            # Lógica de entrada (11 controladores)
│   │   ├── AuthController.php   PerfilController.php     DashboardController.php
│   │   ├── UsuarioController.php EspecialidadController.php MedicoController.php
│   │   ├── PacienteController.php CitaController.php     HorarioController.php
│   │   └── HistorialController.php ReporteController.php
│   ├── models/                 # Acceso a datos y reglas de negocio (consultas preparadas)
│   │   ├── Model.php  Usuario.php  Especialidad.php  Medico.php  Paciente.php
│   │   └── Cita.php  Horario.php  Historial.php  Reporte.php
│   └── database/
│       ├── schema.sql          # Tablas, FKs, índices, triggers (validación) y vistas
│       ├── seed.sql            # Datos de prueba (roles, especialidades, médicos, citas...)
│       └── migrate.php         # Migrador CLI: schema + seed + contraseñas
│
├── frontend/                   # FRONTEND
│   ├── views/
│   │   ├── layouts/            # app.php (autenticado) · auth.php (público)
│   │   ├── auth/               # login · register · forgot
│   │   ├── shared/             # profile · error · appointment-form · pagination · cancel-form
│   │   ├── admin/              # Panel, usuarios, médicos, especialidades, reportes
│   │   ├── receptionist/       # Panel, pacientes, formulario de paciente, citas
│   │   ├── doctor/             # Panel, agenda, horarios, pacientes, historial
│   │   └── patient/            # Panel, citas, historial, perfil
│   └── public/                 # DocumentRoot de Apache
│       ├── index.php           # Punto de entrada único (Front Controller)
│       ├── .htaccess           # Reescritura
│       └── assets/ css · js
│
├── Dockerfile · docker-compose.yml
├── .gitignore · .dockerignore
└── README.md
```

**Regla de oro:** si es PHP que decide cosas → `backend/`. Si se muestra en el
navegador (HTML, plantillas, CSS, JS) → `frontend/`. El DocumentRoot apunta a
`frontend/public`, así que ni `views/` ni `backend/` son accesibles por URL.

---

## 2. Puesta en marcha

### Opción A — Docker (recomendada)

```bash
docker compose up -d --build
# App:  http://localhost:8080
# BD:   postgresql://clinica:clinica@db:5432/clinica   (red interna del compose)
# BD desde el host: localhost:5433 (evita conflictos con otra PostgreSQL local)
```

El contenedor `migrate` ejecuta `backend/database/schema.sql` + `seed.sql` y establece las
contraseñas de prueba antes de levantar la app.

### Opción B — Local (XAMPP / Apache + PostgreSQL)

1. Crea la base de datos:
   ```sql
   CREATE DATABASE clinica;
   ```
2. Configura las credenciales en `backend/config/config.php` (o variables `DB_*`).
3. Ejecuta la migración:
   ```bash
   php backend/database/migrate.php          # limpia y reconstruye: --fresh
   ```
4. Apunta Apache a la carpeta `frontend/public/` (DocumentRoot) o usa:
   ```bash
   php -S localhost:8080 -t frontend/public
   ```

### Opción C — Solo SQL (nube: Neon, Supabase, RDS, Aiven...)

```bash
psql "postgres://user:pass@host:5432/dbname" -f backend/database/schema.sql
psql "postgres://user:pass@host:5432/dbname" -f backend/database/seed.sql
```
> `seed.sql` usa la extensión `pgcrypto` (disponible en todos los servicios gestionados)
> para generar hashes bcrypt. Si no la tienes, ejecuta `php backend/database/migrate.php`,
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

## 8. Correo real (recuperación de contraseña)

El flujo **sí envía correo**. Funciona así:

1. El usuario escribe su correo en `¿Olvidaste tu contraseña?`.
2. Se crea un token aleatorio de 64 caracteres; **en la BD solo se guarda su hash
   SHA-256** (tabla `password_resets`), con 60 minutos de vigencia.
3. Se envía un correo con el enlace `index.php?r=auth/restablecer&token=…`.
4. Al guardar la nueva contraseña se invalidan **todos** los tokens del usuario.

> La respuesta web es **idéntica exista o no el correo**, para que nadie pueda
> averiguar qué direcciones están registradas.

### ⚠ Render bloquea el SMTP

Medido desde un servicio real en Render (diagnóstico propio, 2026-10):

| Destino | Resultado |
|---|---|
| `api.resend.com`, `api.brevo.com`, `api.sendgrid.com` | ✅ funciona |
| `smtp.gmail.com`, `smtp-relay.brevo.com`, `smtp.sendgrid.net`, `smtp.mailgun.org`, Office365, AWS SES… | ❌ **timeout** en 25/465/587 |

El firewall de Render **bloquea todo el tráfico SMTP saliente**, no es un
problema de credenciales. Por eso `Mailer` soporta drivers de **API HTTPS**:

```bash
MAIL_DRIVER=resend     # o 'brevo'
MAIL_API_KEY=...       # clave de la API
MAIL_FROM=...          # remitente verificado en la plataforma
```

Proveedores gratuitos compatibles:

- **Resend** (<https://resend.com>) — 3.000 correos/mes. Se registra con GitHub,
  la clave está en <https://resend.com/api-keys>.
- **Brevo** (<https://brevo.com>) — 300 correos/día.

### En local (SMTP de Gmail sí funciona)

```bash
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USER=tu.correo@gmail.com
MAIL_PASS=...            # contraseña de aplicación
```

Si **no** hay credenciales, el sistema no falla: escribe el mensaje completo en
`backend/storage/mail.log` (driver `log` o degradación automática), lo que
permite probar el flujo entero sin enviar nada.

---

## 9. Despliegue a la nube

### Arquitectura

| Pieza | Dónde vive | Por qué |
|---|---|---|
| App PHP + Apache (Docker) | **Render** (servicio web) | Ejecuta tu `Dockerfile` tal cual |
| PostgreSQL 16 | **Render** (base de datos gestionada) | Se enlaza con `DATABASE_URL` automáticamente |
| Landing informativa | **Vercel** | Es estática y no necesita servidor |

> **Vercel no ejecuta Docker ni Apache** ni ofrece base de datos persistente: por
> eso la app va a Render y Vercel solo recibe la landing.

### A. Render — la aplicación

1. Sube el proyecto a GitHub (si no está, `git push origin main`).
2. En Render: **New → Blueprint** → selecciona el repositorio.
3. Render detecta `render.yaml` y crea:
   - la base de datos `clinica-db` (PostgreSQL, gratis),
   - el servicio web `clinica-vidasalud` (Docker, gratis),
   - las migraciones, que se ejecutan **solo al arrancar el contenedor**.
4. En **Environment** completa las tres claves de correo marcadas como
   `sync: false`: `MAIL_USER`, `MAIL_FROM` y `MAIL_PASS`.
5. Ajusta `APP_BASE_URL` a la URL real que asignó Render
   (p. ej. `https://clinica-vidasalud-xxxx.onrender.com`).
6. Abre esa URL y entra con `admin` / `Vida#Salud2026!`.

Detalles del despliegue:

- `docker/entrypoint.sh` hace que Apache escuche en el puerto `PORT` que asigna
  Render (10000) y ejecuta las migraciones reintentando hasta que la BD esté lista.
- La sonda de salud es `/healthz`: comprueba PHP **y** PostgreSQL y devuelve `200 ok`.
- El plan gratuito se suspende tras 15 min sin uso; la primera petición tarda
  ~40 s en despertar.

### B. Vercel — la landing

1. Crea la cuenta en <https://vercel.com> y **Add New → Project**.
2. Importa el mismo repositorio.
3. En **Settings → General → Root Directory** pon `landing`
   (allí está el `index.html` y el `vercel.json`).
4. Deploy. Obtienes una URL tipo `https://clinica-vidasalud.vercel.app`.
5. Dentro de la landing, los botones "Entrar al sistema" apuntan a Render.

Para cambiar el destino de esos botones sin tocar el HTML, edita la variable
`window.APP_URL` en el `<script>` final de `landing/index.html`.

### C. Otras opciones

La app es un contenedor estándar, así que sirve igual en **Railway**, **Fly.io**,
**Cyclic**, **Google Cloud Run** o un VPS con Docker: solo hace falta exponer el
puerto, montar un PostgreSQL y definir las variables de `.env.example`.

---

## 10. Notas de producción

El `index.php` actual usa Tailwind **Play CDN** para cero configuración. En producción
compila Tailwind:

```bash
npm i -D tailwindcss @tailwindcss/forms
npx tailwindcss -i resources/css/app.css -o frontend/public/assets/css/tailwind.css --minify
```

y reemplaza el `<script src="https://cdn.tailwindcss.com">` por la hoja compilada.
