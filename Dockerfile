FROM php:8.2-apache

# Extensión PDO PostgreSQL + rewrite
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Las variables de entorno del contenedor deben llegar a PHP (mod_env).
# La lista definitiva se genera al arrancar en docker/entrypoint.sh.
RUN printf 'PassEnv DB_HOST DB_PORT DB_NAME DB_USER DB_PASS APP_NAME APP_BASE_URL\n' \
        > /etc/apache2/conf-available/passenv.conf \
    && a2enconf passenv

# DocumentRoot -> frontend/public
RUN sed -ri 's!/var/www/html!/var/www/html/frontend/public!g' \
        /etc/apache2/sites-available/000-default.conf

# El backend nunca debe quedar expuesto: solo se copia su código,
# que vive fuera del DocumentRoot.
COPY backend/ /var/www/html/backend/
COPY frontend/ /var/www/html/frontend/
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
