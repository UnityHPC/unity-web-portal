<?php

namespace UnityWebPortal\lib;

require_once "../../resources/autoload.php";

ini_set("assert.exception", true);

$_SERVER = [
    "REMOTE_ADDR" => "127.0.0.1"
];

function switch_to_user(string $eppn, string $given_name, string $sn, string $mail): void {
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $USER, $LOC_HEADER, $LOC_FOOTER;
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

$pi_1 = ["user1@org1.test", "Givenname", "Surname", "user1@org1.test"];
$user_in_pi_1 = ["user2@org1.test", "Givenname", "Surname", "user2@org1.test"];
$new_user = ["FIXME", "Givenname", "Surname", "FIXME"];
$valid_ssh_key = "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar";
$invalid_ssh_key = "foobar AAAAC3NzaC1lZDI1NTE5AAAAIDWG37i3uTdnanD8SCY2UCUcuqYEszvb/eebyqfUHiRn foobar";

switch_to_user(...$user_in_pi_1);

$initial_ssh_keys = $USER->getSSHKeys(true);

// test paste valid key twice and then delete
$new_key_index = count($USER->getSSHKeys(true));
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
post(
    "../../webroot/panel/account.php",
    [
        "form_type" => "addKey",
        "add_type" => "paste",
        "key" => $valid_ssh_key
    ]
);
assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));
$count_before_duplicate_add = count($USER->getSSHKeys(true));
post(
    "../../webroot/panel/account.php",
    [
        "form_type" => "addKey",
        "add_type" => "paste",
        "key" => $valid_ssh_key
    ]
);
assert(count($USER->getSSHKeys(true)) == $count_before_duplicate_add);
post(
    "../../webroot/panel/account.php",
    ["form_type" => "delKey", "delIndex" => $new_key_index]
);
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test paste invalid key
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
post(
    "../../webroot/panel/account.php",
    [
        "form_type" => "addKey",
        "add_type" => "paste",
        "key" => $invalid_ssh_key
    ]
);
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test import valid key twice and then delete
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
$tmp = tmpfile();
$tmp_path = stream_get_meta_data($tmp)["uri"];
fwrite($tmp, $valid_ssh_key);
$_FILES["keyfile"] = ["tmp_name" => $tmp_path];
post(
    "../../webroot/panel/account.php",
    ["form_type" => "addKey", "add_type" => "import"]
);
unlink($tmp_path);
unset($_FILES["keyfile"]);
assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));
post(
    "../../webroot/panel/account.php",
    ["form_type" => "delKey", "delIndex" => $new_key_index]
);
assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// test import invalid key
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
$tmp = tmpfile();
$tmp_path = stream_get_meta_data($tmp)["uri"];
fwrite($tmp, $invalid_ssh_key);
$_FILES["keyfile"] = ["tmp_name" => $tmp_path];
post(
    "../../webroot/panel/account.php",
    ["form_type" => "delKey", "delIndex" => $new_key_index]
);
unlink($tmp_path);
unset($_FILES["keyfile"]);
assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// // test generate key
// assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
// post(
//     "../../webroot/panel/account.php",
//     [
//         "form_type" => "addKey",
//         "add_type" => "paste",
//         "key" => $valid_ssh_key
//     ]
// );
// assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// // test github valid
// assert(!in_array($valid_ssh_key, $USER->getSSHKeys(true)));
// post(
//     "../../webroot/panel/account.php",
//     [
//         "form_type" => "addKey",
//         "add_type" => "paste",
//         "key" => $valid_ssh_key
//     ]
// );
// assert(in_array($valid_ssh_key, $USER->getSSHKeys(true)));

// // test github invalid
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));
// post(
//     "../../webroot/panel/account.php",
//     [
//         "form_type" => "addKey",
//         "add_type" => "paste",
//         "key" => $invalid_ssh_key
//     ]
// );
// assert(!in_array($invalid_ssh_key, $USER->getSSHKeys(true)));

// test that everything is the way we found it
assert($USER->getSSHKeys(true) == $initial_ssh_keys);
