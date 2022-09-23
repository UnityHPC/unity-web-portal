# Contributing to the Unity Web Portal

## Branch Structure

Each release version has its own branch. Once that version is released, the branch is locked.

When submitting pull requests, the pull request should be made to the version you are targetting, assuming it is not already released.

## Conventions

This code base is currently using PHP version 7.4. All files are required to be linted with PSR-12 standard. This repository will automatically check PRs for linting compliance.

## Development Environment

### Setting up your Environment

1. Download and install [docker desktop](https://www.docker.com/products/docker-desktop/) for your appropriate OS.
1. In `tools/docker-dev` Run the build script: `./build.sh` (mac os/linux) or `./build.bat` (windows)
1. Run the environment: `./run.sh` (mac os/linux) or `./run.bat` (windows). Press `CTRL+C` to exit.

### Environment Usage

While the environment is running, the following is accessible:

* http://127.0.0.1:8000 - Web Portal
* http://127.0.0.1:8010 - PHPLDAPAdmin Portal
* http://127.0.0.1:8020 - PHPMyAdmin Portal
* http://127.0.0.1:8030 - Mailcatcher Portal

### Test Users

The test environment ships with a number of users that can be used for testing. When accessing locked down portions of the portal, you will be asked for a username and password. The password is always `password`.

The following users are available for testing:

* `admin1@domain.com` - admin user who is a member of pi_user1_domain_edu
* `admin2@domain.com` - admin user
* `user1@domain.com` - user who is the owner of pi_user1_domain_edu
* `user2@domain.com` - user who is the owner of pi_user2_domain_edu
* `user3@domain.com` - user who is a member of pi_user1_domain_edu
* `user4@domain.com` - user
* `user5@domain.com` - user who is a member of pi_user2_domain_edu
* `user6@domain.com` - user who is a member of pi_user2_domain_edu
* `user7@domain.com` - user who has no LDAP object
* `user8@domain.com` - user who has no LDAP object
* `user9@domain.com` - user who has no LDAP object
* `user1@domain2.com` - user who is the owner of pi_user1_domain2_edu
* `user2@domain2.com` - user
* `user3@domain2.com` - user who has no LDAP object
* `user4@domain2.com` - user who has no LDAP object

### Changes to Dev Environment

Should the default schema of the web portal change, the `ldap/bootstrap.ldif` and `sql/bootstrap.sql` must be updated for the LDAP server and the MySQL server, respectively.