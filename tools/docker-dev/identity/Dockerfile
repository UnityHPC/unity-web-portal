FROM ubuntu:20.04

# OpenLDAP Server
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    slapd \
    ldap-utils
RUN rm -rf /var/lib/ldap
RUN mkdir /var/lib/ldap
RUN chown openldap:openldap /var/lib/ldap
COPY unity-config.ldif /tmp/unity-config.ldif
COPY ssh.ldif /etc/ldap/schema/ssh.ldif
COPY unity-add.ldif /tmp/unity-add.ldif
COPY initial-objects.ldif /tmp/initial-objects.ldif

RUN service slapd start; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/cosine.ldif; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/nis.ldif; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/inetorgperson.ldif; \
    ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/ldap/schema/ssh.ldif; \
    ldapmodify -Y EXTERNAL -H ldapi:/// -f /tmp/unity-config.ldif; \
    slapadd -l /tmp/unity-add.ldif; \
    slapadd -l /tmp/initial-objects.ldif;

RUN rm -rf /tmp/unity-config.ldif
RUN rm -rf /tmp/unity-add.ldif
RUN rm -rf /tmp/initial-objects.ldif

EXPOSE 389

CMD ["slapd", "-h", "ldap:/// ldapi:///", "-u", "openldap", "-g", "openldap", "-d", "stats"]