# Contributing to the Unity Web Portal

## Branch Structure

Each release version has its own branch. Once that version is released, the branch is locked.

When submitting pull requests, the pull request should be made to the version you are targetting, assuming it is not already released.

## Conventions

This code base is currently using PHP version 8.3. All files are required to be linted with PSR-12 standard. This repository will automatically check PRs for linting compliance.

### handling HTTP headers

* the web page header `LOC_HEADER` should be included before handling HTTP headers
* all expected headers should be fetched using `UnitySite::array_get_or_bad_request`
* all headers which are expected to be one of a set of hard coded values should use a switch case where the default case is `UnitySite::bad_request("invalid <header-name>")`

### admin access control

All pages under `admin/` should check `$USER->isAdmin()` and do `UnitySite::forbidden($USER, $SQL)` if not admin. This should be redundant since the web server should also be doing this on `admin/` as a whole.

### error messages

Use `UnitySite::alert` to make a popup. Be sure to break out of whatever logic branch you're in.

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

The following users are available for testing:

* `web_admin@unityhpc.test` - portal administrator, also has PI group `pi_web_admin_unityhpc_test`

The test environment ships with a randomly generated (with hard coded seed) set of organizations, PI groups, and user accounts. See `tools/docker-dev/generate-user-bootstrap-files-.py`. Use PHPLDAPAdmin to view them. The UIDs are of the form `user0001_org01_test`, `user0002_org02_test`, ... . The lowest user numbers are deliberatly left out, so that you can test the creation of new users. To log in as a user, you can clear your cookies and do HTTP basic auth with their `mail` attribute and password "password", or you can log in as `web_admin` and switch to their account from the user management page.

### Changes to Dev Environment

Should the default schema of the web portal change, `tools/generate_htpasswd_bootstrap-ldif.py` and `sql/bootstrap.sql` must be updated for the LDAP server and the MySQL server, respectively.
