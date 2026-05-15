FROM php:8.2-apache

# 1. SOLUCIÓN RADICAL PARA RAILWAY (MPM ERROR)
# Eliminamos físicamente los archivos de mpm_event.
# En Railway, si no borras estos archivos, el healthcheck fallará con AH00534.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_event.conf \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 2. CONFIGURACIÓN DE PUERTO (Railway usa la variable PORT, pero 8080 es estándar)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/DocumentRoot \/var\/www\/html/a \
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>' /etc/apache2/sites-available/000-default.conf

# 3. INSTALAR SQLITE
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

# 4. ARCHIVOS Y TRABAJO
WORKDIR /var/www/html
COPY . /var/www/html/

# 5. PERMISOS CRÍTICOS (Para evitar errores de base de datos en Railway)
# Railway usa un sistema de archivos efímero a menos que montes un volumen.
RUN mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

# Railway necesita que Apache corra en el foreground
CMD ["apache2-foreground"]
