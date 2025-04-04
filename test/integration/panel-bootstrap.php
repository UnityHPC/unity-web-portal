<?php

$_SERVER = [
    "REMOTE_ADDR" => "127.0.0.1"
];

require "../../resources/autoload.php";

ini_set("assert.exception", false);
ini_set("assert.warning", true);

function switch_to_user(string $eppn, string $given_name, string $sn, string $mail): void {
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
    unset($SSO);
    // unset($_SESSION);
    session_write_close();
    session_id(str_replace(["_", "@", "."], "-", $eppn));
    // init.php will call session_start()
    $_SERVER["REMOTE_USER"] = $eppn;
    $_SERVER["eppn"] = $eppn;
    $_SERVER["givenName"] = $given_name;
    $_SERVER["sn"] = $sn;
    ob_start();
    require "../../resources/autoload.php";
    $_ = ob_get_clean();
    assert(isset($OPERATOR));
    assert(isset($SSO));
    assert(isset($_SESSION));
    assert(isset($USER));
    assert(isset($SITE));
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
