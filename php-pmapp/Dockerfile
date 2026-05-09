FROM php:8.2-apache

# MPM conflict fix
RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork rewrite

# MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Files copy karo
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
