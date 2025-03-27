FROM ubuntu:24.04

ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    apache2 \
    apache2-utils \
    php \
    php-curl \
    php-mysql \
    php-ldap \
    php-pdo \
    php-redis \
    php-cli \
    composer

# PHP dependencies
COPY composer.json /var/www/unity-web-portal/composer.json
RUN cd /var/www/unity-web-portal && composer update

# Web Server Setup
COPY htpasswd /etc/apache2/.htpasswd
COPY unity-apache.conf /etc/apache2/sites-available/unity.conf
RUN a2dissite 000-default
RUN a2ensite unity
RUN echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf

RUN sed -i '/display_errors/c\display_errors = on' /etc/php/8.3/apache2/php.ini

# Start apache2 server
EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
