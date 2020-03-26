FROM php:7.2-apache

COPY ./pukiwiki/ /var/www/html/

RUN cd /var/www/html

RUN mkdir -p -m 777 attach
RUN mkdir -p -m 777 backup
RUN mkdir -p -m 777 cache
RUN mkdir -p -m 777 counter
RUN mkdir -p -m 777 diff
RUN mkdir -p -m 777 wiki

EXPOSE 80