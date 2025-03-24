FROM ubuntu:24.04

# OpenLDAP Server
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    slapd \
    ldap-utils \
    apache2 \
    phpldapadmin
RUN rm -rf /var/lib/ldap
RUN mkdir /var/lib/ldap
RUN chown openldap:openldap /var/lib/ldap
COPY ssh.ldif /etc/ldap/schema/ssh.ldif
COPY ldap-config.ldif /tmp/ldap-config.ldif
COPY bootstrap.ldif /tmp/bootstrap.ldif

RUN service slapd start; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/cosine.ldif; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/nis.ldif; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/inetorgperson.ldif; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/ssh.ldif; \
    ldapmodify -Y EXTERNAL -H ldapi:/// -f /tmp/ldap-config.ldif; \
    slapadd -l /tmp/bootstrap.ldif

RUN rm -rf /tmp/ldap-config.ldif
RUN rm -rf /tmp/bootstrap.ldif

# PHPLDAPAdmin Setup
RUN rm -rf /etc/phpldapadmin/config.php
COPY phpldapadmin-config.php /etc/phpldapadmin/config.php
COPY phpldapadmin-apache.conf /etc/apache2/sites-available/phpldapadmin.conf
RUN a2dissite 000-default
RUN a2ensite phpldapadmin
RUN echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf

EXPOSE 80
EXPOSE 389

CMD apache2ctl -D FOREGROUND & slapd -h "ldap:/// ldapi:///" -u openldap -g openldap -d 0
