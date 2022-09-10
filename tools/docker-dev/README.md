# Unity Web Portal Dev Environment

## Environment Setup

1. Download and install [docker desktop](https://www.docker.com/products/docker-desktop/) for your appropriate OS.
1. Run the build script: `./build.sh`
1. Run the environment: `./run.sh`. Press `CTRL+C` to exit.

## Environment Usage

While the run script is running, the following is accessible:

* http://127.0.0.1:8000 - Web Portal
* http://127.0.0.1:8010 - PHPLDAPAdmin Portal
* http://127.0.0.1:8020 - PHPMyAdmin Portal
* http://127.0.0.1:8030 - Mailcatcher Portal

### Test Users ###

The test environment ships with a number of users that can be used for testing. When accessing locked down portions of the portal, you will be asked for a username and password. The password is always `password`.

The following users are available for testing:

* `admin1@domain.edu` - admin user who is a member of pi_user1_domain_edu
* `admin2@domain.edu` - admin user
* `user1@domain.edu` - user who is the owner of pi_user1_domain_edu
* `user2@domain.edu` - user who is the owner of pi_user2_domain_edu
* `user3@domain.edu` - user who is a member of pi_user1_domain_edu
* `user4@domain.edu` - user
* `user5@domain.edu` - user who is a member of pi_user2_domain_edu
* `user6@domain.edu` - user who is a member of pi_user2_domain_edu
* `user7@domain.edu` - user who has no LDAP object
* `user8@domain.edu` - user who has no LDAP object
* `user9@domain.edu` - user who has no LDAP object
* `user1@domain2.edu` - user who is the owner of pi_user1_domain2_edu
* `user2@domain2.edu` - user
* `user3@domain2.edu` - user who has no LDAP object
* `user4@domain2.edu` - user who has no LDAP object

## Changes to Dev Environment ##

Should the default schema of the web portal change, the `ldap/bootstrap.ldif` and `sql/bootstrap.sql` must be updated for the LDAP server and the MySQL server, respectively.