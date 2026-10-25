FROM php:8.2-apache

# Extensión PDO PostgreSQL + rewrite
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Las variables de entorno del contenedor deben llegar a PHP (mod_env)
RUN printf 'PassEnv DB_HOST DB_PORT DB_NAME DB_USER DB_PASS APP_NAME APP_BASE_URL\n' \
        > /etc/apache2/conf-available/passenv.conf \
    && a2enconf passenv

# DocumentRoot -> /public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
