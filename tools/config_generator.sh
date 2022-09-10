#!/bin/bash

# web portal deploy script

echo "Settings up LDAP Details..."

read -p "Enter LDAP Server Hostname: " ldap_host
read -p "Enter LDAP Server Admin Bind DN: " ldap_user
read -sp "Enter LDAP Server Admin Bind Password: " ldap_pass
read -p "Enter LDAP Server Base DN: " ldap_basedn

read -p "Enter LDAP Server User OU [ou=users,<base dn>]: " ldap_userou
ldap_userou=${ldap_userou:-ou=users,$ldap_basedn}

read -p "Enter LDAP Server Group OU [ou=groups,<base dn>]: " ldap_groupou
ldap_groupou=${ldap_groupou:-ou=groups,$ldap_basedn}

read -p "Enter LDAP Server PI Group OU [ou=pi_groups,<base dn>]: " ldap_pigroupou
ldap_pigroupou=${ldap_pigroupou:-ou=pi_groups,$ldap_basedn}

read -p "Enter LDAP Server Org Group OU [ou=org_groups,<base dn>]: " ldap_orggroupou
ldap_orggroupou=${ldap_orggroupou:-ou=org_groups,$ldap_basedn}

echo "Setting up MySQL Details..."

read -p "Enter MySQL/MariaDB Server Hostname: " sql_host

read -p "Enter MySQL/MariaDB Server Username [unity]: " sql_user
mysql_user=${mysql_user:-unity}

read -sp "Enter MySQL/MariaDB Server Password: " sql_pass

read -p "Enter MySQL/MariaDB Server DB Name [unity]: " sql_db
mysql_db=${mysql_db:-unity}

echo "Setting up notifications..."

read -p "Send email updates about user requests to admins (Y/n)? " smtp_admyn
if [ "$smtp_admyn" = "Y" ] || [ "$smtp_admyn" = "y" ]; then
    read -p "What email should updates be sent to? " smtp_admemail
fi

read -p "Enter SMTP Server Hostname: " smtp_host
read -p "Enter SMTP Port (Usually 22, 465, or 587)" smtp_port
read -p "Enter SMTP Security (None,SSL,STARTTLS)" smtp_secure
read -p "Does SMTP Server Require Login? " smtp_loginyn
if [ "$smtp_loginyn" = "Y" ] || [ "$smtp_loginyn" = "y" ]; then
    read -p "Enter SMTP Server Username: " smtp_user
    read -sp "Enter SMTP Server Password: " smtp_pass
fi

read -p "Enter sender email (usually updates@domain.com): " smtp_sender


# Generate INI File

read -p "Where to output config.ini [config.ini]? " config_path
config_path=${config_path:-config.ini}

touch $config_path
chmod 400 $config_path

echo "[ldap]" > $config_path
echo "host = ${ldap_host}" >> $config_path
echo "user = ${ldap_user}" >> $config_path
echo "pass = ${ldap_pass}" >> $config_path
echo "basedn = ${ldap_pass}" >> $config_path
echo "user_ou = ${ldap_userou}" >> $config_path
echo "group_ou = ${ldap_groupou}" >> $config_path
echo "pigroup_ou = ${ldap_pigroupou}" >> $config_path
echo "orggroup_ou = ${ldap_orggroupou}" >> $config_path

echo "" >> $config_path
echo "[sql]" >> $config_path
echo "host = ${sql_host}" >> $config_path
echo "user = ${sql_user}" >> $config_path
echo "pass = ${sql_pass}" >> $config_path
echo "dbname = ${sql_dbname}" >> $config_path

echo "" >> $config_path
echo "[smtp]" >> $config_path
echo "host = ${smtp_host}" >> $config_path
echo "port = ${smtp_port}" >> $config_path
echo "security = ${smtp_secure}" >> $config_path
echo "auth = ${smtp_loginyn}" >> $config_path
echo "user = ${smtp_user}" >> $config_path
echo "pass = ${smtp_pass}" >> $config_path
echo "send_adm = ${smtp_admyn}" >> $config_path
echo "email_adm = ${smtp_admemail}" >> $config_path
echo "email_sender = ${smtp_sender}" >> $config_path

echo "Wrote ini file to $config_path"