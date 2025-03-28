<?php
namespace UnityWebPortal\lib;

include "../../resources/autoload.php";

global $CONFIG;

$_SERVER = [
    "REMOTE_ADDR" => "127.0.0.1"
];

$pi_1_uid = ["user1@org1.test", "Givenname", "Surname", "user1@org1.test"];
$user_in_pi_1_uid = ["user2@org1.test", "Givenname", "Surname", "user2@org1.test"];
$new_user_uid = ["FIXME", "Givenname", "Surname", "FIXME"];

function switch_to_user(string $eppn, string $given_name, string $sn, string $mail): UnityUser {
    global $CONFIG;
    unset($OPERATOR);
    unset($SSO);
    // unset($_SESSION);
    session_write_close();
    session_id(str_replace("@", "-", str_replace(".", "-", $eppn)));
    // init.php will call session_start()
    $_SERVER["REMOTE_USER"] = $eppn;
    $_SERVER["eppn"] = $eppn;
    $_SERVER["givenName"] = $given_name;
    $_SERVER["sn"] = $sn;
    include "../../resources/init.php";
    assert(isset($OPERATOR));
    assert(isset($SSO));
    assert(isset($_SESSION));
    return $OPERATOR;
}

function post(string $phpfile, array $post_data): void {
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST = $post_data;
    include $phpfile;
    unset($_POST);
    unset($_SERVER["REQUEST_METHOD"]);
}

$user = switch_to_user(...$user_in_pi_1_uid);
echo $user->getUID() . "\n";
