FROM php:8.2-apache
WORKDIR /var/www/html

# Copy all files
COPY . .

# Set write permissions for logs
RUN chown -R www-data:www-data /var/www/html/logs

EXPOSE 80
