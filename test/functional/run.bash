#!/bin/bash
# here is where we will select which users are right for which tests
ARGS="--display-incomplete --display-skipped --display-deprecations --display-phpunit-deprecations --display-errors --display-notices --display-warnings --bootstrap=./bootstrap.php --colors=always $*"
# # TODO make sure this runs with users with 0 keys, > 0 keys, and max keys
# REMOTE_USER=web_admin@unityhpc.test ../../vendor/bin/phpunit $ARGS ./SSHKeyAddTest.php
# # TODO make sure this runs with users with 0 keys, > 0 keys, and max keys
# REMOTE_USER=web_admin@unityhpc.test ../../vendor/bin/phpunit $ARGS ./SSHKeyDeleteTest.php
# REMOTE_USER=web_admin@unityhpc.test ../../vendor/bin/phpunit $ARGS ./LoginShellSetTest.php
# TODO make sure this also runs with a PI user
REMOTE_USER=user0100@org04.edu ../../vendor/bin/phpunit $ARGS ./PiBecomeRequestTest.php
