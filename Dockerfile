FROM php:8.2-apache

# 1. LIMPIEZA AGRESIVA DE MPM
# Eliminamos físicamente los archivos de carga y configuración de event y worker
# tanto de la carpeta de disponibles como de la de activados.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-available/mpm_event.* \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.* /etc/apache2/mods-available/mpm_worker.* \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 2. Configurar Apache (Puerto 8080 y Directory)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/DocumentRoot \/var\/www\/html/a \
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>' /etc/apache2/sites-available/000-default.conf

# 3. SQLite y Extensiones
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

# 4. Archivos y Permisos
WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /data \
    && chown -R www-data:www-data /var/www/html /data \
    && chmod -R 755 /var/www/html \
    && chmod 777 /data

EXPOSE 8080
