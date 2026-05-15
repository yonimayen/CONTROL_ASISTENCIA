FROM php:8.2-apache

# 1. SOLUCIÓN AL ERROR MPM: Borrado físico de archivos duplicados
# Esto elimina cualquier rastro de otros MPM antes de activar prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.load \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 2. Configurar Apache: Puerto 8080 y permisos de Directorio
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/DocumentRoot \/var\/www\/html/a \
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>' /etc/apache2/sites-available/000-default.conf

# 3. Instalar dependencias de sistema y SQLite
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# 4. Instalar extensiones de PHP
RUN docker-php-ext-install pdo pdo_sqlite

# 5. Configurar archivos y permisos
WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /data \
    && chown -R www-data:www-data /var/www/html /data \
    && chmod -R 755 /var/www/html \
    && chmod 777 /data

EXPOSE 8080
