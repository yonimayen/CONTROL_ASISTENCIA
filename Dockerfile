FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides and listen on port 8080
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html\n\t<Directory /var/www/html>\n\t\tAllowOverride All\n\t\tRequire all granted\n\t</Directory>|' /etc/apache2/sites-available/000-default.conf

# Install SQLite development library and PDO extensions
RUN apt-get update && apt-get install -y libsqlite3-dev && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Create persistent data directory and set permissions
RUN mkdir -p /data \
    && chown -R www-data:www-data /var/www/html /data \
    && chmod -R 755 /var/www/html \
    && chmod 777 /data

EXPOSE 8080
