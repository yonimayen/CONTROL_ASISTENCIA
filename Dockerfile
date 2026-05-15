FROM php:8.2-apache

# Solución al error de MPM y habilitar rewrite
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Configuración de puerto 8080 y permisos de .htaccess
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/DocumentRoot \/var\/www\/html/a \
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>' /etc/apache2/sites-available/000-default.conf

# Instalación de SQLite y extensiones PHP
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /var/www/html
COPY . /var/www/html/

# Permisos para SQLite y archivos
RUN mkdir -p /data \
    && chown -R www-data:www-data /var/www/html /data \
    && chmod -R 755 /var/www/html \
    && chmod 777 /data

EXPOSE 8080
