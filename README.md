![UNITY](https://user-images.githubusercontent.com/40907639/137608695-2d914da2-1ecc-480b-a47e-a9e33b2b1b45.png)

# Unity Web Portal
Unity Web Portal is a PHP application built in top of MariaDB and LDAP which acts as a central user portal for high-performance-computing clusters. 

Basic Features:
   * User Signup
       * PIs require admin approval, users require PI approval
   * User SSH public key management
       * no passwords
       * Github import, upload file, paste, generate and download private key
   * User changes login shell
   * User requests their own PI group
   * PI approves/denies requests to join their PI group
   * PI removes members from their group

Admin features:
   * Automatic updating of LDAP to reflect current state of users, groups, organizations, PI groups
   * Cluster notices
       * Added to front page, mailed, and exposed via REST API
   * WYSIWYG HTML editor for webpage contents, cluster notices
   * Branding customization for multiple domains simultaneously
   * Custom UIDNumber / GIDNumber mappings for specific users
   * Login as another user
   * Mailing

## Installation/Deployment

See the Docker Compose environment (`tools/docker-dev/`) for an (unsafe for production) example.

1. OpenLDAP server
    * Structure should be similar to `tools/docker-dev/identity/bootstrap.ldif` <!-- TODO separate OUs from entries -->
    * Also see `tools/docker-dev/identity/{config,ssh}.ldif`
1. MySQL / MariaDB server
    * Structure should be similar to `tools/docker-dev/sql/bootstrap.sql` <!-- TODO separate structure from data -->
1. SMTP server
1. Web server
    * `unity-web` user and group should exist
    * This repository cloned, with files owned by `unity-web:unity-web`
    * Submodules checked out (`git submodule update --checkout --init`)
    * Composer (`apt install composer` on Ubuntu)
    * Dependencies:
        * PHP extensions
            * cli, curl, intl, ldap, mbstring, mysql, pdo, redis, xml (`apt install php-<extension>` on Ubuntu)
        * Libraries
            * `composer update`
    * `httpd` should run as the `unity-web` user/group
    * `httpd` `DocumentRoot` set to `webroot/`
    * `httpd` Authentication
        * Any authentication will do as long as it defines `REMOTE_USER`, `givenName`, `sn`, and `mail`
            * `REMOTE_USER` must also be unique, non-reassignable, and persistent
        * Unity uses Shibboleth SP and the Apache Shibboleth module (`apt install shibboleth-sp-utils libapache2-mod-shib` on Ubuntu)
    * `httpd` Authorization
        * Global access to `webroot/panel/`
        * Restricted access to `webroot/admin/`
        * No access anywhere else

## Configuration
1. Create `deployment/config/config.ini` using `/deployment/defaults/config.ini` as a reference
    * Make sure this file is not world readable!
1. If using mulitple domains, create `deployment/overrides/<domain>/config/config.ini`
1. If using custom UIDNumber/GIDNumber mappings, create `deployment/custom_user_mappings/*.csv`
1. Add logos to `webroot/assets/footer_logos/`

## Integration
The scope of this project ends at being responsible for the LDAP user database. We recommend production deployments to set up scripts which detect changes in LDAP and then perform further actions. For example, Unity uses such scripts to create home directories and add records to the Slurm account database.

## Updating
We recommend a deployment where each version of the portal is its own clone, then just change a symlink to point to the new version. This way a rollback is much easier.

Example folder structure, where `->` indicates a symlink:
```
/var/www/
    unity-web-portal -> unity-web-portal-1.1.0
    unity-web-portal-1.1.0
    unity-web-portal-1.2.0
```

Update instructions assuming the above structure:

```shell
url="https://..."
prod="/var/www/unity-web-portal"
old="/var/www/unity-web-portal-1.1.0"
new="/var/www/unity-web-portal-1.2.0"

mkdir "$new" && cd "$new"
git clone "$url" .
git submodule update --init --checkout
composer update
cp "$prod/deployment/config/config.ini" ./deployment/config/config.ini
rsync -a "$prod/deployment/custom_user_mappings/" ./deployment/custom_user_mappings/
rsync -a "$prod/deployment/overrides/" ./deployment/overrides/
rsync -a "$prod/webroot/assets/footer_logos/" ./footer_logos/
rm "$prod" && ln -s "$PWD" "$prod"
```

Rollback:

```shell
rm "$prod" && ln -s "$old" "$prod"
```

### Version-specific update instructions:

### 1.0 -> 1.1

* `config/branding/config.ini.default` has some new fields that will need to be overriden by the site config if needed:
   * `pi_approve*` in the `mail` section
   * `home` in the `page` section
   * The entire `loginshell` section
* In SQL db be sure to add the `home` content management row

### 1.1 -> 1.2
* Create the `audit_log` table (see `bootstrap.sql` for details)
* Create the `account_deletion_requests` table (see `bootstrap.sql` for details)
* Create the `user_last_logins` table (see `bootstrap.sql` for details)
* Drop the `sso_log` table
* Drop the `events` table
* Reduce the size of all `varchar(1000)` columns to `varchar(768)`

### 1.2.0 -> 1.2.1
* Add new columns to the `requests` table:
   ```sql
   ALTER TABLE `requests`
   ADD `firstname` VARCHAR(768) NOT NULL AFTER `timestamp`,
   ADD `lastname` VARCHAR(768) NOT NULL AFTER `firstname`,
   ADD `email` VARCHAR(768) NOT NULL AFTER `lastname`,
   ADD `org` VARCHAR(768) NOT NULL AFTER `email`; 
   ```
