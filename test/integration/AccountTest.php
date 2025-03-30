<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;
use PHPUnit\Framework\TestCase;

require_once "../../resources/autoload.php";

ini_set("assert.exception", false);
ini_set("assert.warning", true);

$_SERVER = [
    "REMOTE_ADDR" => "127.0.0.1"
];

function switch_to_user(string $eppn, string $given_name, string $sn, string $mail): void {
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
    unset($SSO);
    // unset($_SESSION);
    session_write_close();
    session_id(str_replace("@", "-", str_replace(".", "-", $eppn)));
    // init.php will call session_start()
    $_SERVER["REMOTE_USER"] = $eppn;
    $_SERVER["eppn"] = $eppn;
    $_SERVER["givenName"] = $given_name;
    $_SERVER["sn"] = $sn;
    ob_start();
    include "../../resources/init.php";
    $_ = ob_get_clean();
    assert(isset($OPERATOR));
    assert(isset($SSO));
    assert(isset($_SESSION));
    assert(isset($USER));
}

function post(string $phpfile, array $post_data): string {
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $USER, $LOC_HEADER, $LOC_FOOTER;
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST = $post_data;
    ob_start();
    include $phpfile;
    $output = ob_get_clean();
    unset($_POST);
    unset($_SERVER["REQUEST_METHOD"]);
    return $output;
}

function delete_ssh_key(int $index): void {
    post(
        "../../webroot/panel/account.php",
        ["form_type" => "delKey", "delIndex" => $index]
    );
}

function add_ssh_key_paste(string $key): void {
    post(
        "../../webroot/panel/account.php",
        [
            "form_type" => "addKey",
            "add_type" => "paste",
            "key" => $key
        ]
    );
}

function add_ssh_key_import(string $key): void {
    $tmp = tmpfile();
    $tmp_path = stream_get_meta_data($tmp)["uri"];
    fwrite($tmp, $key);
    $_FILES["keyfile"] = ["tmp_name" => $tmp_path];
    post(
        "../../webroot/panel/account.php",
        ["form_type" => "addKey", "add_type" => "import"]
    );
    unlink($tmp_path);
    unset($_FILES["keyfile"]);
}

function add_ssh_key_generated(string $key): void {
    post(
        "../../webroot/panel/account.php",
        [
            "form_type" => "addKey",
            "add_type" => "generate",
            "gen_key" => $key
        ]
    );
}

function add_ssh_key_github(string $key): void {
    // requires phpunit `@runTestsInSeparateProcesses` and `@preserveGlobalState disabled`
    $mock = Mockery::mock("overload:\UnityWebPortal\lib\UnitySite");
    $mock->shouldReceive("getGithubKeys")->with("foobar")->andReturn(json_encode([["key" => $key]]));
    post(
        "../../webroot/panel/account.php",
        [
            "form_type" => "addKey",
            "add_type" => "github",
            "gh_user" => "foobar"
        ]
    );
    Mockery::close();
}

$pi_1 = ["user1@org1.test", "Givenname", "Surname", "user1@org1.test"];
$user_in_pi_1 = ["user2@org1.test", "Givenname", "Surname", "user2@org1.test"];
$new_user = ["FIXME", "Givenname", "Surname", "FIXME"];
$valid_ssh_key = "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar";
$invalid_ssh_key = "foobar AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar";

switch_to_user(...$user_in_pi_1);

$initial_ssh_keys = $USER->getSSHKeys(true);

// test paste valid key that doesn't exist yet
$new_key_index = count($USER->getSSHKeys(true));
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_paste($valid_ssh_key);
assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test paste valid key that already exists
$count_before_duplicate_add = count($USER->getSSHKeys(true));
assert($count_before_duplicate_add > 0);
add_ssh_key_paste($valid_ssh_key);
assert(count($USER->getSSHKeys(true)) == $count_before_duplicate_add);

// cleanup
delete_ssh_key($new_key_index);
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test paste invalid key
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_paste($invalid_ssh_key);
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test import valid key that doesn't exist yet
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_import($valid_ssh_key);
assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test import valid key that already exists
$count_before_duplicate_add = count($USER->getSSHKeys(true));
assert($count_before_duplicate_add > 0);
add_ssh_key_import($valid_ssh_key);
assert(count($USER->getSSHKeys(true)) == $count_before_duplicate_add);

// cleanup
delete_ssh_key($new_key_index);
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test import valid key that doesn't exist yet
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_import($invalid_ssh_key);
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test generated valid key that doesn't exist yet
$new_key_index = count($USER->getSSHKeys(true));
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_generated($valid_ssh_key);
assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test generated valid key that already exists
$count_before_duplicate_add = count($USER->getSSHKeys(true));
assert($count_before_duplicate_add > 0);
add_ssh_key_generated($valid_ssh_key);
assert(count($USER->getSSHKeys(true)) == $count_before_duplicate_add);

// cleanup
delete_ssh_key($new_key_index);
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test generated invalid key
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_generated($invalid_ssh_key);
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
// class AccountTest extends TestCase {
//     private $user_in_pi_1 = ["user2@org1.test", "Givenname", "Surname", "user2@org1.test"];
//     private $valid_ssh_key = "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar";
//     // private $pi_1 = ["user1@org1.test", "Givenname", "Surname", "user1@org1.test"];
//     // private $user_in_pi_1 = ["user2@org1.test", "Givenname", "Surname", "user2@org1.test"];
//     // private $new_user = ["FIXME", "Givenname", "Surname", "FIXME"];
//     // private $valid_ssh_key = "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar";
//     // private $invalid_ssh_key = "foobar AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar";

//     private function add_ssh_key_github(string $key): void {
//         $mock = Mockery::mock("overload:\UnityWebPortal\lib\UnitySite");
//         $mock->shouldReceive("getGithubKeys")->with("foobar")->andReturn(json_encode([["key" => $key]]));
//         post(
//             "../../webroot/panel/account.php",
//             [
//                 "form_type" => "addKey",
//                 "add_type" => "github",
//                 "gh_user" => "foobar"
//             ]
//         );
//         Mockery::close();
//     }


//     public function testAccountSshKeyAddGithubValid(): void {
//         global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
//         require_once "../../resources/autoload.php";

//         ini_set("assert.exception", false);
//         ini_set("assert.warning", true);

//         $_SERVER = [
//             "REMOTE_ADDR" => "127.0.0.1"
//         ];
//         switch_to_user(...$this->user_in_pi_1);
//         // test github valid key that doesn't exist yet
//         $new_key_index = count($USER->getSSHKeys(true));
//         assert(!in_array($this->valid_ssh_key, $USER->getSSHKeys(true)));
//         $this->add_ssh_key_github($this->valid_ssh_key);
//         assert(in_array($this->valid_ssh_key, $USER->getSSHKeys(true)));
//     }
// }

// test github valid key that doesn't exist yet
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_github($invalid_ssh_key);
assert(in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test github valid key that already exists
$count_before_duplicate_add = count($USER->getSSHKeys(true));
assert($count_before_duplicate_add > 0);
add_ssh_key_github($valid_ssh_key);
assert(count($USER->getSSHKeys(true)) == $count_before_duplicate_add);

// cleanup
delete_ssh_key($new_key_index);
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// // test github invalid key
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
// add_ssh_key_github($invalid_ssh_key);
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test that everything is the way we found it
assert($USER->getSSHKeys(true) == $initial_ssh_keys);
