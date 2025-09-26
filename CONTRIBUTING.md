# Contributing to the Unity Web Portal

## Conventions

* PHP version 8.3.
* All files are required to be linted with PSR-12 standard.
* The maximum line length for any PHP file is 100 characters, instead of PSR-12's 120 characters.
* Comments should be used sparingly.
* Empty lines should be used sparingly.
* No code should call `die()` or `exit()`, instead `UnityHTTPD::die()`. This will avoid the premature death of our automated testing processes.
* Instead of `assert`, use `\ensure`. This will enforce conditions even in production.

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

## Testing

Github Actions are used to execute all following tests for all pull requests.
This means that if you're feeling lazy, you don't have to go out of your way to run tests, you just have to wait a bit longer for Github to do it for you.

### `pre-commit`

We use `pre-commit` for enforcing (and sometimes automatically fixing) the PSR-12 code standard, whitespace discrepancies, syntax validity, secret leak detection, and whatever else can be done quickly.
`pre-commit` runs automatically every time you commit, assuming you set it up correctly.
To save time, `pre-commit` only runs on the files with staged changes.
To run on all files, use `pre-commit run --all-files`.

### `phpunit`

Since this codebase was not written with testing in mind, and this codebase makes extensive use of external resources such as SQL and LDAP, most of our testing does not focus on isolated "units", but high level functionality.
Our functional tests pretend to make HTTP requests by modifying global variables in the same way that a production webserver would.
This is preferred over directly calling library code because it helps to test the PHP logic in the webpages themselves, rather than just the internals.
For example, one functional test would be to set `$_SERVER["REMOTE_USER"]` to authenticate as a user, `require "resources/init.php"` to setup the `$USER` global variable, set `$_POST["key"] = "ssh-rsa ..."` and `require "webroot/panel/account.php"` to make that user enter a new SSH key in the HTML form in the `account.php` page.
Once a user action has been taken, internal interfaces are used to verify the results.

To run `phpunit`, spawn 2 shells in differnt tabs:

tab 1:
```shell
cd ./tools/docker-dev
./build.sh
./run.sh
```

tab 2:
```
$ container="$(docker container ls | grep web | awk '{print $1}')"
$ docker exec -it "$container" bash
> cd /var/www/unity-web
> ./vendor/bin/phpunit /path/to/tests
```

For `/path/to/tests/`, you usually want `./test/functional/` but you can select a specific file to save time when troubleshooting specific tests.

### code coverage

`phpunit` has code coverage built in.
It recommends the use of "strict code coverage", where every single test explicitly lists what functions it covers.
That's a lot of work, so instead we accept what phpunit refers to as "risky unintentionally covered code".
Using [robiningelbrecht/phpunit-coverage-tools](https://github.com/robiningelbrecht/phpunit-coverage-tools), our Github Actions testing will fail if the coverage falls below a certain percentage of lines of code.
This percentage should be increased over time to just below whatever the current coverage is.

To run a code coverage test, use the same procedure for phpunit but add this argument: `--coverage-text=/dev/stdout`

### LDAP cleanliness

Any test that makes changes to LDAP must clean up after itself using `try`/`finally`.
When a test fails to clean up after itself, it can cause other tests to fail or become otherwise un-useful.
Because LDAP may not always be clean, any test that relies on a certain LDAP state should `assert` everything about that state.
To reset LDAP to a clean slate, just re-run the `build.sh` script.

Note: `phpunit` can misbehave when using `expectException` and `try`/`finally`, see https://github.com/UnityHPC/unity-web-portal/issues/258.

### creating the conditions for a test

Selecting users for tests happens with the `get...User...` family of functions from `phpunit-bootstrap.php`.
Since this family of functions is growing large and their names long and complicated, it is better to start with a simpler state and create the desired conditions manually.
For example, rather than using `getUserWithOneKey`, use `getUserHasNoSSHKeys` and add one key for them.

The LDAP entries available in the dev environment are defined in `tools/docker-dev/identity/bootstrap.ldif`.
These entries may be subject to change.
Only `phpunit-bootstrap.php` should have hard-coded references to these entries.

### testing the HTTP API

When writing a test, it may be tempting to use the PHP API directly, but the HTTP API must also be tested.

Example:

using the PHP API:
```php
private function requestGroupCreation()
{
    $USER->getPIGroup()->requestGroup();
}
```

using the HTTP API:
```php
private function requestGroupCreation()
{
    http_post(
        __DIR__ . "/../../webroot/panel/new_account.php",
        ["new_user_sel" => "pi", "eula" => "agree", "confirm_pi" => "agree"]
    );
}
```

`http_post` is defined in `phpunit-bootstrap.php`.

It is fine to use the PHP API when making assertions and doing cleanup.
