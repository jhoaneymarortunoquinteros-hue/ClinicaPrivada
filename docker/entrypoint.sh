#!/bin/sh
# ------------------------------------------------------------------
# Arranque del contenedor.
#   1. Apache escucha en $PORT (Render asigna 10000; Docker local usa 80).
#   2. Exporta a PHP las variables de entorno (Apache las filtra por defecto).
#   3. Ejecuta las migraciones reintentando mientras la base de datos arranca.
#   4. Arranca Apache.
# ------------------------------------------------------------------
set -e

# Si el servicio trae su propio comando (p. ej. `php .../migrate.php`),
# se ejecuta tal cual y NO se arranca Apache.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

PORT="${PORT:-80}"

# --- 1. Puerto de escucha ------------------------------------------
if [ -f /etc/apache2/ports.conf ]; then
    sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
fi

# --- 2. PassEnv: mod_env solo revela a PHP lo que se le indique -----
VAR_LIST=$(env | cut -d= -f1 \
    | grep -E '^(DB_[A-Z_]*|APP_[A-Z_]*|MAIL_[A-Z_]*|DATABASE_URL|POSTGRES_URL|PORT)$' \
    | sort | tr '\n' ' ')

if [ -n "$VAR_LIST" ]; then
    printf 'PassEnv %s\n' "$VAR_LIST" > /etc/apache2/conf-available/passenv.conf
    a2enconf passenv >/dev/null 2>&1 || true
fi

# --- 3. Migraciones (esperan a que la BD esté disponible) -----------
APP_DIR=/var/www/html
attempt=1
max_attempts=12
while [ "$attempt" -le "$max_attempts" ]; do
    if php "$APP_DIR/backend/database/migrate.php" > /tmp/migrate.log 2>&1; then
        echo "== Migraciones aplicadas (intento $attempt) =="
        cat /tmp/migrate.log
        break
    fi
    echo "== BD no disponible (intento $attempt/$max_attempts) =="
    if [ "$attempt" -eq "$max_attempts" ]; then
        echo "== AVISO: no se pudo migrar; se arranca igualmente =="
        cat /tmp/migrate.log
    fi
    attempt=$((attempt + 1))
    sleep 5
done

# --- 4. Apache ------------------------------------------------------
exec apache2-foreground
