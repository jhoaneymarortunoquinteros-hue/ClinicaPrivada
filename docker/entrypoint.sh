#!/bin/sh
# ------------------------------------------------------------------
# Arrancho del contenedor. Todo lo crítico se escribe AQUÍ, en tiempo
# de ejecución, para que no dependa de la caché de build del registry:
#
#   1. Apache escucha en $PORT (Render suele usar 10000; Docker local, 80)
#   2. VirtualHost con DocumentRoot /var/www/html/frontend/public
#      y AllowOverride All (sin esto, .htaccess se ignora y /healthz da 404)
#   3. Exporta a PHP las variables de entorno (Apache las filtra por defecto)
#   4. Migraciones, reintentando mientras la base de datos arranca
#   5. Diagnóstico en el log + Apache en primer plano
# ------------------------------------------------------------------
set -e

# Si el servicio trae su propio comando (p. ej. `php .../migrate.php`),
# se ejecuta tal cual y NO se arranca Apache.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

PORT="${PORT:-80}"

# --- 1. Puerto de escucha ------------------------------------------
printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf

# --- 2. VirtualHost explícito --------------------------------------
# Se genera entero aquí: si el DocumentRoot no estuviera bien, la app
# entera devolvería 404 y ni siquiera llegaríamos a PHP.
cat > /etc/apache2/sites-available/000-default.conf <<VHOST
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/frontend/public

    <Directory /var/www/html/frontend/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
VHOST

a2enmod rewrite >/dev/null 2>&1 || true

# --- 3. PassEnv: mod_env solo revela a PHP lo que se le indique ----
VAR_LIST=$(env | cut -d= -f1 \
    | grep -E '^(DB_[A-Z_]*|APP_[A-Z_]*|MAIL_[A-Z_]*|DATABASE_URL|POSTGRES_URL|PORT|TZ)$' \
    | sort | tr '\n' ' ' || true)

if [ -n "$VAR_LIST" ]; then
    printf 'PassEnv %s\n' "$VAR_LIST" > /etc/apache2/conf-available/passenv.conf
    a2enconf passenv >/dev/null 2>&1 || true
fi

# --- 4. Migraciones (esperan a que la BD esté disponible) ----------
APP_DIR=/var/www/html
attempt=1
max_attempts=12
while [ "$attempt" -le "$max_attempts" ]; do
    if php "$APP_DIR/backend/database/migrate.php" > /tmp/migrate.log 2>&1; then
        echo "== Migraciones aplicadas (intento $attempt) =="
        tail -n 3 /tmp/migrate.log
        break
    fi
    echo "== BD no disponible aun (intento $attempt/$max_attempts) =="
    if [ "$attempt" -eq "$max_attempts" ]; then
        echo "== AVISO: no se pudo migrar; se arranca igualmente =="
        cat /tmp/migrate.log
    fi
    attempt=$((attempt + 1))
    sleep 5
done

# --- 5. Diagnóstico (se ve en los logs de Render) -------------------
echo "== Config Apache =="
echo "   PORT       = $PORT"
echo "   DATABASE_URL = $([ -n "$DATABASE_URL" ] && echo definida || echo AUSENTE)"
echo "   MAIL_USER    = ${MAIL_USER:-AUSENTE}"
echo "   MAIL_HOST    = ${MAIL_HOST:-AUSENTE}"
echo "   APP_BASE_URL = ${APP_BASE_URL:-AUSENTE}"
grep -E '^Listen' /etc/apache2/ports.conf || true
grep -E 'DocumentRoot' /etc/apache2/sites-available/000-default.conf || true
echo "   DocumentRoot contiene:"
ls /var/www/html/frontend/public/ 2>/dev/null | head -n 8 || echo "     (VACIO o inexistente)"
apache2ctl -S 2>&1 | head -n 12 || true

# --- 6. Apache ------------------------------------------------------
exec apache2-foreground
