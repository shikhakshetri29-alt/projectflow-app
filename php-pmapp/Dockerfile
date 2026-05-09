FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Seedha MPM files delete karo aur sirf prefork enable karo
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load && \
    rm -f /etc/apache2/mods-enabled/mpm_*.conf && \
    ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ && \
    ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ && \
    a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
