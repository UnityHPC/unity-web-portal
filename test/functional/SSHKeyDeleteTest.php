<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;

require_once "panel-bootstrap.php";
require_once "panel-account-ssh-keys.php";

class SSHKeyDeleteTest {
    private static function test_delete_ssh_key(mixed $index): void {
        global $USER, $CONFIG, $SITE;
        $keys_before = $USER->getSSHKeys();
        $expect_bad_request_exception = false;
        if (is_string($index) && !preg_match("/^[0-9]+$/", $index)) {
            $expected_keys_after = $keys_before;
            $expect_bad_request_exception = true;
        } elseif ($index < 0){
            $expected_keys_after = $keys_before;
            $expect_bad_request_exception = true;
        } elseif ($index >= count($keys_before)){
            $expected_keys_after = $keys_before;
            $expect_bad_request_exception = true;
        } else {
            $expected_keys_after = $keys_before;
            unset($expected_keys_after[intval($index)]);
        }
        ob_start();
        try {
            self::delete_ssh_key($index);
        } catch (BadRequestException $e) {
            if (!$expect_bad_request_exception) {
                throw $e;
            }
        } finally {
            $output = ob_get_clean();
        }
        $keys_after = $USER->getSSHKeys();
        assert($keys_after == $expected_keys_after);
        // FIXME set back to original state
    }

    private static function test_delete_ssh_keys_all_inputs(){
        global $CONFIG;
        $inputs = [-1, 0, intval($CONFIG["ldap"]["max_num_ssh_keys"]), "foobar"];
        foreach ($inputs as $input){
            echo "###############################################################\n";
            echo "delete key index '$input'\n";
            self::test_delete_ssh_key($input);
            echo "\n";
        }
    }

    public static function test_delete_ssh_keys_all_inputs_multi_users(){
        // user with 0 keys
        switch_to_user("web_admin@unityhpc.test", "Web", "Admin", "web_admin@unityhpc.test");
        self::test_delete_ssh_keys_all_inputs();

        // user with > 0 keys and > 0 key slots open
        switch_to_user("user0110@org22.edu", "Foo", "Bar", "user0110@org22.edu");
        self::test_delete_ssh_keys_all_inputs();

        // user with no empty key slots
        switch_to_user("user0151@org18.edu", "Foo", "Bar", "user0151@org18.edu");
        self::test_delete_ssh_keys_all_inputs();
    }
}


