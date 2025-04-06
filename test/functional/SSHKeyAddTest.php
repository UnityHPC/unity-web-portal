<?php

namespace UnityWebPortal\lib;

use Mockery;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SSHKeyAddTest extends TestCase {
    private static $initial_keys;

    public static function setUpBeforeClass(): void{
        global $USER;
        self::$initial_keys = $USER->getSSHKeys();
    }

    protected function tearDown(): void {
        global $USER;
        $USER->setSSHKeys(self::$initial_keys);
    }

    private static function get_max_number_of_valid_ssh_keys(){
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

    public static function ssh_key_provider() {
        global $HTTP_HEADER_TEST_INPUTS;
        $max_number_of_valid_ssh_keys = self::get_max_number_of_valid_ssh_keys();
        return [
            [[]],
            [["foobar"]],
            [[$max_number_of_valid_ssh_keys[0]]],
            [[$max_number_of_valid_ssh_keys[0], "foobar"]],
            [$max_number_of_valid_ssh_keys],
            [array_slice($max_number_of_valid_ssh_keys, 0, -1) + ["foobar"]],
        ] + array_map(function($x){return [[$x]];}, $HTTP_HEADER_TEST_INPUTS);
        // TODO don't need to do HTTP_HEADER_TEST_INPUTS if we add unit tests
    }

    private function add_ssh_key_paste(string $key): void {
        post(
            "../../webroot/panel/account.php",
            [
                "form_type" => "addKey",
                "add_type" => "paste",
                "key" => $key
            ]
        );
    }

    private function add_ssh_key_import(string $key): void {
        $tmp = tmpfile();
        $tmp_path = stream_get_meta_data($tmp)["uri"];
        fwrite($tmp, $key);
        $_FILES["keyfile"] = ["tmp_name" => $tmp_path];
        try {
            post(
                "../../webroot/panel/account.php",
                ["form_type" => "addKey", "add_type" => "import"]
            );
        } finally {
            unlink($tmp_path);
            unset($_FILES["keyfile"]);
        }
    }

    private function add_ssh_key_generate(string $key): void {
        post(
            "../../webroot/panel/account.php",
            [
                "form_type" => "addKey",
                "add_type" => "generate",
                "gen_key" => $key
            ]
        );
    }

    private function add_ssh_keys_github(array $keys): void {
        global $SITE;
        $old_site = $SITE;
        $SITE = Mockery::mock(UnitySite::class)->makePartial();
        $SITE->shouldReceive("getGithubKeys")->with("foobar")->andReturn($keys);
        try {
            post(
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

    private function test_add_ssh_keys_not_github(array $add_keys, Callable $func): string{
        // test adding SSH keys using any non-github method, taking into account how many
        // keys the user already has, how many are valid, and how many the user is
        // actually allowed to import before they hit the limit
        // at the end, return keys to their initial state
        global $USER, $CONFIG, $SITE;
        $new_keys = array_diff($add_keys, self::$initial_keys);
        $new_valid_keys = array_filter(
            $new_keys,
            function($x) {global $SITE; return $SITE->testValidSSHKey($x);}
        );
        $open_key_slots = $CONFIG["ldap"]["max_num_ssh_keys"] - count(self::$initial_keys);
        if (count($new_valid_keys) > $open_key_slots) {
            $expected_added_keys = array_slice($new_valid_keys, 0, $open_key_slots);
        } else {
            $expected_added_keys = $new_valid_keys;
        }
        $expected_keys_after = array_merge(self::$initial_keys, $expected_added_keys);
        ob_start();
        foreach($add_keys as $new_key){
            $func($new_key);
        }
        $output = ob_get_clean();
        $keys_after = $USER->getSSHKeys();
        // error_log(json_encode([
        //     "method" => $func_name,
        //     "keys_before" => self::$initial_keys,
        //     "add_keys" => $add_keys,
        //     "new_keys" => $new_keys,
        //     "new_valid_keys" => $new_valid_keys,
        //     "open_key_slots" => $open_key_slots,
        //     "expected_added_keys" => $expected_added_keys,
        //     "expected_keys_after" => $expected_keys_after,
        //     "keys_after" => $keys_after,
        // ], JSON_PRETTY_PRINT));
        $this->assertEquals($expected_keys_after, $keys_after);
        return $output;
    }

    #[DataProvider("ssh_key_provider")]
    public function test_add_ssh_keys_paste(array $add_keys): string{
        return $this->test_add_ssh_keys_not_github($add_keys, $this->add_ssh_key_paste(...));
    }

    #[DataProvider("ssh_key_provider")]
    public function test_add_ssh_keys_import(array $add_keys): string{
        return $this->test_add_ssh_keys_not_github($add_keys, $this->add_ssh_key_import(...));
    }

    #[DataProvider("ssh_key_provider")]
    public function test_add_ssh_keys_generate(array $add_keys): string{
        return $this->test_add_ssh_keys_not_github($add_keys, $this->add_ssh_key_generate(...));
    }

    #[DataProvider("ssh_key_provider")]
    public function test_add_ssh_keys_github(array $add_keys): string{
        // test adding SSH keys using the github method, taking into account how many
        // keys the user already has, how many are valid, and how many the user is
        // actually allowed to import before they hit the limit
        // at the end, return keys to their initial state
        // test adding SSH keys using any non-github method, taking into account how many
        // keys the user already has, how many are valid, and how many the user is
        // actually allowed to import before they hit the limit
        // at the end, return keys to their initial state
        global $USER, $CONFIG, $SITE;
        $new_keys = array_diff($add_keys, self::$initial_keys);
        $new_valid_keys = array_filter(
            $new_keys,
            function($x) {global $SITE; return $SITE->testValidSSHKey($x);}
        );
        $open_key_slots = $CONFIG["ldap"]["max_num_ssh_keys"] - count(self::$initial_keys);
         // github key add method does nothing if limit is reached or any key is invalid
        if ((count($new_valid_keys) != count($new_keys)) || (count($add_keys) > $open_key_slots)){
            $expected_keys_after = self::$initial_keys;
        } else {
            $expected_keys_after = self::$initial_keys + $add_keys;
        }
        ob_start();
        $this->add_ssh_keys_github($add_keys);
        $output = ob_get_clean();
        $keys_after = $USER->getSSHKeys();
        // error_log(json_encode([
        //     "method" => "github",
        //     "keys_before" => self::$initial_keys,
        //     "add_keys" => $add_keys,
        //     "new_keys" => $new_keys,
        //     "new_valid_keys" => $new_valid_keys,
        //     "open_key_slots" => $open_key_slots,
        //     "expected_added_keys" => $expected_added_keys,
        //     "expected_keys_after" => $expected_keys_after,
        //     "keys_after" => $keys_after,
        // ], JSON_PRETTY_PRINT));
        $this->assertEquals($expected_keys_after, $keys_after);
        return $output;
    }
}
