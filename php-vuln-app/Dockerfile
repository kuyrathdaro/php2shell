FROM php:8.5.11-apache

WORKDIR /var/www/html

COPY src/ /var/www/html/

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
