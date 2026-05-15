FROM php:8.2-apache

# 1. Corregir conflicto de MPM y habilitar mod_rewrite
# Usamos || true para que no falle si el módulo ya está desactivado
RUN a2dismod mpm_event || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 2. Configurar Apache: Puerto 8080 y permisos de Directorio
# Se ajusta el puerto y se inyecta la configuración de .htaccess de forma más limpia
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/DocumentRoot \/var\/www\/html/a \
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>' /etc/apache2/sites-available/000-default.conf

# 3. Instalar dependencias de sistema (SQLite)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# 4. Instalar extensiones de PHP
RUN docker-php-ext-install pdo pdo_sqlite

# 5. Configurar directorio de trabajo
WORKDIR /var/www/html

# 6. Copiar archivos del proyecto
COPY . /var/www/html/

# 7. Crear directorio para persistencia de SQLite y ajustar permisos
# Es vital que el usuario www-data sea dueño de la carpeta donde esté el .sqlite
RUN mkdir -p /data \
    && chown -R www-data:www-data /var/www/html /data \
    && chmod -R 755 /var/www/html \
    && chmod 777 /data

EXPOSE 8080

# El comando por defecto ya es apache2-foreground en esta imagen
