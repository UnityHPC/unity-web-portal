![UNITY](https://user-images.githubusercontent.com/40907639/137608695-2d914da2-1ecc-480b-a47e-a9e33b2b1b45.png)

# Unity Web Portal
Unity Web Portal is a PHP application built in top of MariaDB and LDAP which acts as a central user portal for high-performance-computing clusters. 

Basic Features:
   * User signs up
       * PIs require admin approval, users require PI approval
   * User manages SSH public keys
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
    * This repository cloned
    * `deployment/config/config.ini` should be owned by the apache user (`www-data` on Ubuntu), with mode `0600`
    * Submodules checked out (`git submodule update --checkout --init`)
    * Composer (`apt install composer` on Ubuntu)
    * Dependencies:
        * PHP extensions
            * curl, intl, ldap, mbstring, mysql, pdo, redis, xml (`apt install php-<extension>` on Ubuntu)
        * Libraries
            * `COMPOSER_ALLOW_SUPERUSER=1 composer --no-dev --no-scripts --no-plugins install`
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
prod="/srv/www/unity-web"
old="/srv/www/unity-web-1.1.0"
new="/srv/www/unity-web-1.2.0"

mkdir "$new" && cd "$new"
git clone "$url" .
git submodule update --init --checkout
COMPOSER_ALLOW_SUPERUSER=1 composer --no-dev --no-scripts --no-plugins install
cp --preserve=all "$prod/deployment/config/config.ini" ./deployment/config/config.ini
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

### 1.2.0 -> 1.2.1
* SQL:
    * Add new columns to the `requests` table:
       ```sql
       ALTER TABLE `requests`
       ADD `firstname` VARCHAR(768) NOT NULL AFTER `timestamp`,
       ADD `lastname` VARCHAR(768) NOT NULL AFTER `firstname`,
       ADD `email` VARCHAR(768) NOT NULL AFTER `lastname`,
       ADD `org` VARCHAR(768) NOT NULL AFTER `email`; 
       ```

### 1.1 -> 1.2
* SQL:
    * Create the `sitevars` table (see `bootstrap.sql` for details)
    * Create the `audit_log` table (see `bootstrap.sql` for details)
    * Create the `account_deletion_requests` table (see `bootstrap.sql` for details)
    * Create the `user_last_logins` table (see `bootstrap.sql` for details)
    * Drop the `sso_log` table
    * Drop the `events` table
    * Reduce the size of all `varchar(1000)` columns to `varchar(768)`
    * Delete the `priv` row in the `pages` table (if moving site policy to external site)
    * Add the `account_policy` row in the `pages` table (if NOT moving site policy to external site)
* `defaults/config.ini.default` has some new fields that may need to be overriden:
    * `ldap.user_group`
    * `site.terms_of_service_url`
        * example, created account policy page: `https://unity.rc.umass.edu/panel/account_policy.php`
    * `site.account_policy_url`
        * example, using old site policy page: `https://unity.rc.umass.edu/panel/priv.php`
* LDAP:
    * Create a new group defined by `ldap.user_group` in the config

### 1.0 -> 1.1
* SQL:
  * Add the `home` content management row
* `config/branding/config.ini.default` has some new fields that may need to be overriden:
   * `mail.pi_approve*`
   * `page.home`
   * The entire `loginshell` section
