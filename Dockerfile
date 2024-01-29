FROM php:8.0-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN apt-get update && apt-get upgrade -y

RUN echo "Installed PHP"

COPY /chugbot /var/www/html/

RUN echo "Installed files"

VOLUME ["/etc/mysql", "/var/lib/mysql"]