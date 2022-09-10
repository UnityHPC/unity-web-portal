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
    phpldapadmin \
    phpmyadmin
COPY htpasswd /etc/apache2/.htpasswd
COPY unity.conf /etc/apache2/sites-available/unity.conf
RUN a2dissite 000-default
RUN a2ensite unity

# PHPLDAPAdmin Setup
RUN rm -rf /etc/phpldapadmin/config.php
COPY phpldapadmin-config.php /etc/phpldapadmin/config.php

# PHPMYadmin Setup
COPY phpmyadmin-config.php /usr/share/phpmyadmin/config.inc.php

# Start apache2 server
EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]