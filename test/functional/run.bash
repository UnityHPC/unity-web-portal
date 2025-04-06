#!/bin/bash
# here is where we will select which users are right for which tests
ARGS="--display-incomplete --display-skipped --display-deprecations --display-phpunit-deprecations --display-errors --display-notices --display-warnings --bootstrap=./bootstrap.php --colors=always"
REMOTE_USER=web_admin@unityhpc.test ../../vendor/bin/phpunit $ARGS ./SSHKeyAddTest.php
REMOTE_USER=web_admin@unityhpc.test ../../vendor/bin/phpunit $ARGS ./SSHKeyDeleteTest.php
REMOTE_USER=web_admin@unityhpc.test ../../vendor/bin/phpunit $ARGS ./LoginShellSetTest.php
# public static function test_delete_ssh_keys_all_inputs_all_methods_multi_users(){
#     // user with 0 keys
#     switch_to_user("web_admin@unityhpc.test", "Web", "Admin", "web_admin@unityhpc.test");
#     self::test_add_ssh_keys_all_methods_all_inputs();

#     // user with > 0 keys and > 0 key slots open
#     switch_to_user("user0110@org22.edu", "Foo", "Bar", "user0110@org22.edu");
#     self::test_add_ssh_keys_all_methods_all_inputs();

#     // user with no empty key slots
#     switch_to_user("user0151@org18.edu", "Foo", "Bar", "user0151@org18.edu");
#     self::test_add_ssh_keys_all_methods_all_inputs();
# }
