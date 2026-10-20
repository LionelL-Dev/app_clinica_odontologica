FROM php:8.2-apache

# Instalar el driver de PostgreSQL necesario para conectar tu backend
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

# Copiar todos los archivos de tu repositorio al servidor web
COPY . /var/www/html/

# Exponer el puerto por defecto de Apache
EXPOSE 80
