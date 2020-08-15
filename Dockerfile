FROM php:7.2-apache

COPY ./pukiwiki/ /var/www/html/

EXPOSE 80