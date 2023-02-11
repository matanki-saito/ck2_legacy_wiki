FROM php:7.4.28-apache

COPY ./pukiwiki/ /var/www/html/

RUN sed -ri -e 's!%h %l %u %t!%{X-Forwarded-For}i %l %u %t!g' /etc/apache2/apache2.conf

COPY ./php.ini "$PHP_INI_DIR/php.ini"

EXPOSE 80