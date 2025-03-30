<?php

$_SERVER = [
    "REMOTE_ADDR" => "127.0.0.1"
];

require_once "../../resources/autoload.php";

ini_set("assert.exception", false);
ini_set("assert.warning", true);

function switch_to_user(string $eppn, string $given_name, string $sn, string $mail): void {
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
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
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
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
