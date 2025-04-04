<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;

require_once "panel-bootstrap.php";

class PanelAccountSSHKeyTest {
    static function delete_ssh_key(int $index): string {
        return post(
            "../../webroot/panel/account.php",
            ["form_type" => "delKey", "delIndex" => $index]
        );
    }

    static function add_ssh_key_paste(string $key): string {
        return post(
            "../../webroot/panel/account.php",
            [
                "form_type" => "addKey",
                "add_type" => "paste",
                "key" => $key
            ]
        );
    }

    static function add_ssh_key_import(string $key): string {
        $tmp = tmpfile();
        $tmp_path = stream_get_meta_data($tmp)["uri"];
        fwrite($tmp, $key);
        $_FILES["keyfile"] = ["tmp_name" => $tmp_path];
        try {
            return post(
                "../../webroot/panel/account.php",
                ["form_type" => "addKey", "add_type" => "import"]
            );
        } finally {
            unlink($tmp_path);
            unset($_FILES["keyfile"]);
        }
    }

    static function add_ssh_key_generated(string $key): string {
        return post(
            "../../webroot/panel/account.php",
            [
                "form_type" => "addKey",
                "add_type" => "generate",
                "gen_key" => $key
            ]
        );
    }

    static function add_ssh_keys_github(array $keys): string {
        global $SITE;
        $old_site = $SITE;
        $SITE = Mockery::mock(UnitySite::class)->makePartial();
        $SITE->shouldReceive("getGithubKeys")->with("foobar")->andReturn($keys);
        try {
            return post(
                "../../webroot/panel/account.php",
                [
                    "form_type" => "addKey",
                    "add_type" => "github",
                    "gh_user" => "foobar"
                ]
            );
        } finally {
            Mockery::close();
            $SITE = $old_site;
        }
    }

    static function test_add_ssh_keys_all_methods(array $add_keys){
        // test adding SSH keys using all 4 methods, taking into account how many
        // keys the user already has, how many are valid, and how many the user is
        // actually allowed to upload before they hit the limit
        // at the end, return keys to their initial state
        global $USER, $CONFIG, $SITE;
        $keys_before = $USER->getSSHKeys();
        $new_keys = array_diff($add_keys, $keys_before);
        $new_valid_keys = array_filter(
            $new_keys,
            function($x) {global $SITE; return $SITE->testValidSSHKey($x);}
        );
        $open_key_slots = $CONFIG["ldap"]["max_num_ssh_keys"] - count($keys_before);
        if (count($new_valid_keys) > $open_key_slots) {
            $expected_added_keys = array_slice($new_valid_keys, 0, $open_key_slots);
        } else {
            $expected_added_keys = $new_valid_keys;
        }
        $expected_keys_after = array_merge($keys_before, $expected_added_keys);
        // first 3 key add methods take one key at a time
        foreach([
            "paste" => "self::add_ssh_key_paste",
            "import" => "self::add_ssh_key_import",
            "generate" => "self::add_ssh_key_generated"
        ] as $func_name => $func){
            foreach($add_keys as $new_key){
                call_user_func($func, $new_key);
            }
            $keys_after = $USER->getSSHKeys();
            assert(
                $keys_after == $expected_keys_after,
                json_encode([
                    "method" => $func_name,
                    "keys_before" => $keys_before,
                    "add_keys" => $add_keys,
                    "new_keys" => $new_keys,
                    "new_valid_keys" => $new_valid_keys,
                    "open_key_slots" => $open_key_slots,
                    "expected_added_keys" => $expected_added_keys,
                    "expected_keys_after" => $expected_keys_after,
                    "keys_after" => $keys_after,
                ], JSON_PRETTY_PRINT)
            );
            $USER->setSSHKeys($keys_before);
        }
        // github key add method takes entire list at once
        self::add_ssh_keys_github($add_keys);
        // github key add method does nothing if limit is reached or any key is invalid
        if (
            (count($new_valid_keys) != count($new_keys)) || count($new_valid_keys) > $open_key_slots){
            $expected_keys_after = $keys_before;
        }
        $keys_after = $USER->getSSHKeys();
        assert(
            $keys_after == $expected_keys_after,
            json_encode([
                "method" => "github",
                "keys_before" => $keys_before,
                "add_keys" => $add_keys,
                "new_keys" => $new_keys,
                "new_valid_keys" => $new_valid_keys,
                "open_key_slots" => $open_key_slots,
                "expected_added_keys" => $expected_added_keys,
                "expected_keys_after" => $expected_keys_after,
                "keys_after" => $keys_after,
            ], JSON_PRETTY_PRINT)
        );
        $USER->setSSHKeys($keys_before);
    }

    static function get_max_number_of_valid_ssh_keys(){
        // the docker dev tooling should create some users with their account completely full of
        // unique valid SSH keys. find one of these accounts and return their keys.
        global $CONFIG, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        $uids = $REDIS->getCache("sorted_users", "");
        foreach($uids as $uid){
            $user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            $keys = $user->getSSHKeys();
            if(count($keys) == $CONFIG["ldap"]["max_num_ssh_keys"]){
                return $keys;
            }
        }
        throw new Exception("could not find any user with max ssh keys!");
    }

    static function test_add_ssh_keys_all_methods_all_inputs(){
        $max_number_of_valid_keys = self::get_max_number_of_valid_ssh_keys();
        $inputs = [
            "no keys" => [],
            "single invalid key" => ["foobar"],
            "single valid key" => [$max_number_of_valid_keys[0]],
            "one valid one invalid" => ["foobar", $max_number_of_valid_keys[0]],
            "maximum number of valid keys" => $max_number_of_valid_keys,
            "maximum number of keys, all valid except one" => array_slice($max_number_of_valid_keys, 0, -1) + ["foobar"]
        ];
        foreach ($inputs as $input_name => $input){
            echo "###############################################################\n";
            echo "$input_name\n";
            echo "\n";
            self::test_add_ssh_keys_all_methods($input);
            echo "\n";
        }
    }
}
