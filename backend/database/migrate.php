<?php
declare(strict_types=1);

/**
 * Migrador de base de datos (CLI).
 *
 *   php backend/database/migrate.php            # crea esquema + datos de prueba
 *   php backend/database/migrate.php --fresh    # elimina el esquema y vuelve a crear todo
 *
 * El script:
 *  1. Aplica las migraciones incrementales sobre bases de datos existentes
 *     (columna users.username y correo opcional).
 *  2. Ejecuta backend/database/schema.sql (tablas, FKs, índices, triggers y vistas).
 *  3. Genera el nombre de usuario de las cuentas que no lo tengan.
 *  4. Si la tabla users está vacía, ejecuta backend/database/seed.sql.
 *  5. Re-escribe las contraseñas de los usuarios de prueba con
 *     password_hash() de PHP (garantiza el inicio de sesión aunque el
 *     servicio PostgreSQL no tenga disponible la extensión pgcrypto).
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

define('BASE_PATH', dirname(__DIR__, 2));
define('BACKEND_PATH', dirname(__DIR__));

$GLOBALS['CONFIG'] = require BACKEND_PATH . '/config/config.php';
require BACKEND_PATH . '/config/database.php';
require BACKEND_PATH . '/core/helpers.php';

use App\Core\Database;

$fresh = in_array('--fresh', $argv ?? [], true);
$pdo = Database::pdo();

echo "== Migrador Clínica VidaSalud ==\n";

if ($fresh) {
    $pdo->exec('DROP SCHEMA public CASCADE');
    $pdo->exec('CREATE SCHEMA public');
    echo "[1/5] Esquema eliminado y recreado.\n";
} else {
    echo "[1/5] Usando esquema existente (--fresh para reconstruir).\n";
}

// ---------------------------------------------------------------------
// [2/5] Migraciones incrementales (idempotentes: se pueden repetir).
//  Se ejecutan ANTES de schema.sql porque las vistas de ese archivo
//  referencian la columna users.username.
//  - users.username: nombre de usuario para iniciar sesión.
//  - users.email deja de ser obligatorio (el auto-registro pide username).
// ---------------------------------------------------------------------
$usersTableExists = (bool) $pdo->query(
    "SELECT to_regclass('public.users') IS NOT NULL"
)->fetchColumn();

if ($usersTableExists) {
    $incremental = [
        'ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(40)',
        'ALTER TABLE users ALTER COLUMN email DROP NOT NULL',
        'CREATE UNIQUE INDEX IF NOT EXISTS uq_users_username
             ON users (lower(username)) WHERE username IS NOT NULL',
    ];
    try {
        foreach ($incremental as $sql) {
            $pdo->exec($sql);
        }
        echo "[2/5] Columna username disponible (correo ahora opcional).\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "[ERROR] migración incremental: " . $e->getMessage() . "\n");
        exit(1);
    }
} else {
    echo "[2/5] Base de datos nueva: no hay migraciones previas.\n";
}

$pdo->beginTransaction();
try {
    $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
    $pdo->commit();
    echo "[3/5] schema.sql aplicado (tablas, triggers, vistas).\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "[ERROR] schema.sql: " . $e->getMessage() . "\n");
    exit(1);
}

// Completar el nombre de usuario de las cuentas existentes a partir del correo.
$pending = $pdo->query(
    "SELECT id, email, first_name, last_name
       FROM users
      WHERE username IS NULL OR btrim(username) = ''"
)->fetchAll(PDO::FETCH_ASSOC);

if ($pending) {
    $exists = $pdo->prepare(
        'SELECT COUNT(*) FROM users WHERE lower(username) = lower(:u) AND id <> :id'
    );
    $update = $pdo->prepare('UPDATE users SET username = :u WHERE id = :id');

    foreach ($pending as $row) {
        $base = username_slug((string) ($row['email'] ?? ''));
        if ($base === '') {
            $base = username_slug($row['first_name'] . ' ' . $row['last_name']);
        }
        if ($base === '') {
            $base = 'usuario';
        }
        $base = mb_substr($base, 0, 40, 'UTF-8');

        $candidate = $base;
        $suffix = 2;
        while (true) {
            $exists->execute([':u' => $candidate, ':id' => (int) $row['id']]);
            if ((int) $exists->fetchColumn() === 0) {
                break;
            }
            $candidate = mb_substr($base, 0, 37, 'UTF-8') . '-' . $suffix++;
        }
        $update->execute([':u' => $candidate, ':id' => (int) $row['id']]);
    }
    printf("       %d cuenta(s) recibieron un nombre de usuario.\n", count($pending));
}

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount === 0) {
    $pdo->beginTransaction();
    try {
        $pdo->exec(file_get_contents(__DIR__ . '/seed.sql'));
        $pdo->commit();
        echo "[4/5] seed.sql aplicado (roles, especialidades, médicos, pacientes, citas).\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "[ERROR] seed.sql: " . $e->getMessage() . "\n");
        exit(1);
    }
} else {
    echo "[4/5] Ya existen usuarios: se omite el seed.\n";
}

// Credenciales definitivas de los usuarios de prueba (correo => contraseña).
// Cumplen la política completa: 8+ caracteres, mayúscula, minúscula,
// número y carácter especial.
$credentials = [
    'admin@clinica.test'      => 'Vida#Salud2026!',
    'recepcion@clinica.test'  => 'Agenda$Segura26',
    'cardiologo@clinica.test' => 'Consulta!Medica26',
    'general@clinica.test'    => 'Consulta!Medica26',
    'pediatra@clinica.test'   => 'Consulta!Medica26',
    'maria@paciente.test'     => 'Cita#Privada26',
    'pedro@paciente.test'     => 'Cita#Privada26',
    'lucia@paciente.test'     => 'Cita#Privada26',
];

$stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE email = :email');
$updated = 0;
foreach ($credentials as $email => $password) {
    $stmt->execute([
        ':hash'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]),
        ':email' => $email,
    ]);
    $updated += $stmt->rowCount();
}
echo "[5/5] Contraseñas de prueba actualizadas ({$updated} usuarios).\n\n";

echo "Usuarios de prueba (entra con el nombre de usuario):\n";
try {
    $list = $pdo->prepare(
        'SELECT username, email FROM users WHERE email = :email'
    );
    foreach ($credentials as $email => $password) {
        $list->execute([':email' => $email]);
        $row = $list->fetch(PDO::FETCH_ASSOC);
        printf(
            "   %-16s %-26s %s\n",
            $row['username'] ?? '—',
            $email,
            $password
        );
    }
} catch (Throwable $e) {
    foreach ($credentials as $email => $password) {
        printf("   %-26s  %s\n", $email, $password);
    }
}
echo "\nListo. Abre frontend/public/index.php (o http://localhost:8080 con Docker).\n";
