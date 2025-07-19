FROM php:7.4-apache

# Enable PDO and MySQL extensions
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/
