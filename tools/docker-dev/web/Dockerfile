FROM ubuntu:24.04

# Web Server Setup
ARG DEBIAN_FRONTEND=noninteractive
# php extensions also listed in .github/workflows/phpunit.yml and README.md
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
    php-mbstring \
    php-xml \
    php-intl
COPY htpasswd /etc/apache2/.htpasswd
RUN chown www-data /etc/apache2/.htpasswd
COPY unity-apache.conf /etc/apache2/sites-available/unity.conf
RUN a2dissite 000-default
RUN a2ensite unity
RUN echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf

RUN sed -i '/display_errors/c\display_errors = on' /etc/php/8.3/apache2/php.ini
RUN sed -i '/zend.assertions/c\zend.assertions = 1' /etc/php/8.3/apache2/php.ini
RUN sed -i '/zend.assertions/c\zend.assertions = 1' /etc/php/8.3/cli/php.ini
RUN sed -i '/memory_limit/c\memory_limit = -1' /etc/php/8.3/apache2/php.ini

# Start apache2 server
EXPOSE 80

CMD ["bash", "-c", "pushd /var/www/unity-web-portal/workers >/dev/null && echo 'updating LDAP cache...' && php ./update-ldap-cache.php && popd >/dev/null && apache2ctl -D FOREGROUND"]
