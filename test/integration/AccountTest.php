<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;

require "bootstrap.php";

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
    global $SITE;
    $old_site = $SITE;
    $SITE = Mockery::mock();
    $SITE->shouldReceive("getGithubKeys")->with("foobar")->andReturn([["key" => $key]]);
    $SITE->shouldReceive("testValidSSHKey")->with(["key" => $key])->andReturn(true);
    $SITE->shouldReceive("removeTrailingWhitespace")->with(Mockery::any())->andReturn([$key]);
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
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
// add_ssh_key_generated($invalid_ssh_key);
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test github valid key that doesn't exist yet
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
add_ssh_key_github($valid_ssh_key);
assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test github valid key that already exists
$count_before_duplicate_add = count($USER->getSSHKeys(true));
assert($count_before_duplicate_add > 0);
add_ssh_key_github($valid_ssh_key);
assert(count($USER->getSSHKeys(true)) == $count_before_duplicate_add);

// cleanup
delete_ssh_key($new_key_index);
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test github invalid key
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
// add_ssh_key_github($invalid_ssh_key);
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test that everything is the way we found it
assert($USER->getSSHKeys(true) === $initial_ssh_keys);
