![alt text](https://user-images.githubusercontent.com/40907639/137608695-2d914da2-1ecc-480b-a47e-a9e33b2b1b45.png)

## Unity Cluster Web Portal ##

### Installation ###
1. Prerequisites
    1. OpenLDAP Server with Admin Credentials, correct schema
    1. SQL Server, correct schema!

    1. Slurm commands accessible on this host, `www-data` (or whatever the web server user is) should be an operator in `sacctmgr`
    1. SMTP Server
    1. Some HTTP Authentication mechanism (such as Shibboleth SP)
1. Install required PHP Libraries:
    1. Install composer `apt install composer`
        1. Create directory and navigate to `resources/libraries/composer`
        1. Install phpmailer: `composer require phpmailer/phpmailer`
        1. Install phpseclib: `composer require phpseclib/phpseclib`
    1. Install php-ldap `apt install php-ldap`
1. Setup config File `resources/config.php`, use the `resoures/config.php.example` as a reference
1. Apache Configs



#### Directory Structure ####
* `/webroot` - Public root of the website (http document root)
* `/resources` - Private directory containing php files not necessary to be public.

The unity/webroot directory should be the **only** publicly accessible location (DocumentRoot in htdocs). The resources directory contains many php scripts that are referenced absolutely in the config.

#### Server Setup ####
This website has a public and private interface. The private interface is authenticated using a shibboleth SP. The following files/directories must be behind a shibboleth SP (configured through apache).
* `/panel`
* `/admin` for admins only
