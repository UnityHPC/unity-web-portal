#!/bin/bash

# start apache2
apache2ctl -D FOREGROUND &

# start SLAPD
slapd -h "ldap:/// ldapi:///" -u openldap -g openldap -d 0