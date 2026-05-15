FROM php:8.2-apache

# 1. ELIMINACIÓN AGRESIVA DE MPMs DUPLICADOS
# Borramos los archivos .load de los módulos que causan el conflicto (event y worker)
# Esto asegura que Apache solo vea el que activamos después.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_event.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.load \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.conf \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 2. CONFIGURACIÓN DE PUERTO PARA RAILWAY
# Railway usa puertos variables, pero configurarlo a 8080 es una buena práctica.
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/DocumentRoot \/var\/www\/html/a \
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>' /etc/apache2/sites-available/000-default.conf

# 3. INSTALACIÓN DE SQLITE
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

# 4. ARCHIVOS Y PERMISOS DE BASE DE DATOS
WORKDIR /var/www/html
COPY . /var/www/html/

# IMPORTANTE: SQLite requiere permisos de escritura en el ARCHIVO y en la CARPETA.
# Creamos una carpeta específica para la base de datos si no existe.
RUN mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

EXPOSE 8080

CMD ["apache2-foreground"]
