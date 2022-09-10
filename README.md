![alt text](https://user-images.githubusercontent.com/40907639/137608695-2d914da2-1ecc-480b-a47e-a9e33b2b1b45.png)

## Unity Cluster Web Portal ##

### Installation ###
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
1. Setup config file `resources/config.ini` according to site
1. Point your web server's document root to `webroot` in this repo

### Directory Structure ###
* `/webroot` - Public root of the website (http document root)
* `/resources` - Private directory containing php files not necessary to be public.

The unity/webroot directory should be the **only** publicly accessible location (DocumentRoot in htdocs). The resources directory contains many php scripts that are referenced absolutely in the config.

### Server Setup ###
This website has a public and private interface. The private interface is authenticated using a shibboleth SP. The following files/directories must be behind a shibboleth SP (configured through apache).
* `/panel`
* `/admin` for admins only

### Contributing ###

First, fork this repo, then see the readme in `tools/docker-dev/README.md` to see how to set up a dev environment on your local machine from your fork.

Branch names correspond to version numbers. All commits should be merged via PRs to the version branches during development. Once a version is released the branch cannot be updated.

Be sure to update `CHANGELOG` with any changes by version.