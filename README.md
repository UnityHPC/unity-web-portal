![alt text](https://user-images.githubusercontent.com/40907639/137608695-2d914da2-1ecc-480b-a47e-a9e33b2b1b45.png)

# Unity Web Portal
Unity Web Portal is a PHP application built on top of MariaDB and LDAP which acts as a central user portal for high-performance-computing clusters. Features include:
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
1. Composer packages
    1. `cd` to this repository
    1. Install packages `composer update`
1. Setup config file `config/config.ini` according to your site deployment
1. Point your web server's document root to `webroot` in this repo

The scope of this project ends at being responsible for the LDAP user database. We recommend production deployments to set up scripts which detect changes in LDAP and then perform further actions. For example, a script can be used to create Slurm scheduler accounting roles based on the LDAP information created by this website.

## Web Server Setup
External to this codebase, you must configure authentication using your web server. You must retrict the following:
* `/panel` - users who are signed in
* `/admin` - admins who are signed in
