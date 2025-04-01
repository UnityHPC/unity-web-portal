![alt text](https://user-images.githubusercontent.com/40907639/137608695-2d914da2-1ecc-480b-a47e-a9e33b2b1b45.png)

# Unity Web Portal
Unity Web Portal is a PHP application built in top of MariaDB and LDAP which acts as a central user portal for high-performance-computing clusters. Features include:
* Automation of LDAP object/user creation with SSH public key configurations
* Custom user group creation in LDAP
* Cluster notices
* Content Management
* Modularity for external websites
* Automatic emails to admins/emails
* Robust branding customization
* Custom user options
* Featured admin panel
* Many more features, and more to come!

## Installation/Deployment
1. Web server prerequisites
    1. Accessible OpenLDAP server
    1. Accessible MySQL / MariaDB server
    1. Accessible SMTP Server
    1. Some HTTP Authentication mechanism (such as Shibboleth SP)
    1. Composer (`apt install composer` on Ubuntu)
    1. PHP Extensions
        1. `php-ldap`
        1. `php-curl`
        1. `php-redis`
        1. `php-cli`
        1. `php-mysql`
        1. `php-pdo`
1. Composer packages
    1. `cd` to this repository
    1. Install packages `sudo composer update --no-plugins --no-scripts`
1. Deployment:
    1. configure the files in `deployment/` according to their respective `README.md` files
1. make sure redis cache is populated: `cd workers && php ./update-ldap-cache.php`
1. Point your web server's document root to `webroot` in this repo

The scope of this project ends at being responsible for the LDAP user database. We recommend production deployments to set up scripts which detect changes in LDAP and then perform further actions. For example, a script can be used to create Slurm scheduler accounting roles based on the LDAP information created by this website.

## Web Server Setup
External to this codebase, you must configure authentication using your web server. You must retrict the following:
* `/panel` - users who are signed in
* `/admin` - admins who are signed in

## Updating
The update process is similar to the installation process:

1. Clone the release and follow installation instructions 1 and 2 from above.
2. Copy the following folders from the old installation to the new one:
    1. `deployment`
    2. `webroot/assets/footer_logos`

We recommend a deployment where each version of the portal is its own clone, then just change a symlink to point to the new version. This way a rollback is much easier.

Example folder structure, where `->` indicates a symlink:
```
unity-web-portal
    unity-web-portal -> unity-web-portal-1.1.0
    unity-web-portal-1.0.0-RC1
    unity-web-portal-1.0.0-RC2
    unity-web-portal-1.1.0
```

Below you will find specific instructions for moving between version:

### 1.0.0-RC2 > 1.1.0

1. `config/branding/config.ini.default` has some new fields that will need to be overriden by the site config if needed:
   1. `pi_approve*` in the `mail` section
   2. `home` in the `page` section
   3. The entire `loginshell` section
1. In SQL db be sure to add the `home` content management row
