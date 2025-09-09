# Contributing to the Unity Web Portal

## Conventions

This code base is currently using PHP version 8.3.
All files are required to be linted with PSR-12 standard.
The maximum line length for any PHP file is 100 characters, instead of PSR-12's 120 characters.
This repository will automatically check PRs for linting compliance.

## Development Environment

### Setting up your Environment

1. Clone this repo (including submodules): `git clone <this-repo> --recurse-submodules`
1. install [composer](https://getcomposer.org/)
1. install PHP dependencies: `composer update`
1. If you're on Windows, use [WSL](https://learn.microsoft.com/en-us/windows/wsl/)
1. Download and install [docker desktop](https://www.docker.com/products/docker-desktop/)
1. In `tools/docker-dev` Run the build script: `./build.sh`
1. Run the environment: `./run.sh`. Press `CTRL+C` to exit
1. Install [pre-commit](https://pre-commit.com/)
1. setup pre-commit hooks: `pre-commit install`

### Environment Usage

While the environment is running, the following is accessible:

* http://127.0.0.1:8000 - Web Portal
* http://127.0.0.1:8010 - PHPLDAPAdmin Portal
* http://127.0.0.1:8020 - PHPMyAdmin Portal
* http://127.0.0.1:8030 - Mailcatcher Portal

To run tests:
```
docker exec -it "$(docker container ls | grep web | awk '{print $1}')" bash
cd /var/www/unity-web
./vendor/bin/phpunit /optional/path/to/tests
```

If you bork your LDAP or SQL server, just run `./build.sh` again

### Test Users

The test environment ships with a number of users that can be used for testing.
When accessing locked down portions of the portal, you will be asked for a username and password.
The password is always `password`. `tools/docker-dev/web/htpasswd` contains all valid usernames.

Notable users:
* `user1@org1.test` - admin, PI
* `user2@org1.test` - not admin, not PI
* `user2000@org2.test` - does not yet have an account

### Changes to Dev Environment

Should the default schema of the web portal change, the `ldap/bootstrap.ldif` and `sql/bootstrap.sql` must be updated for the LDAP server and the MySQL server, respectively.
