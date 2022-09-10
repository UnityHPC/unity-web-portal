FROM ubuntu:20.04

# Web Server Setup
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    apache2 \
    apache2-utils \
    php \
    php-curl \
    php-mysql \
    php-ldap \
    php-pdo \
    phpmyadmin
COPY htpasswd /etc/apache2/.htpasswd
COPY unity-apache.conf /etc/apache2/sites-available/unity.conf
RUN a2dissite 000-default
RUN a2ensite unity

# Start apache2 server
EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]