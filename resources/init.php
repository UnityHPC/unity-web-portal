<?php

/**
 * init.php - Initialization script that is run on every page of Unity
 */

use UnityWebPortal\lib\UnityLDAP;
use UnityWebPortal\lib\UnityMailer;
use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UnitySSO;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityWebhook;
use UnityWebPortal\lib\UnityGithub;
use UnityWebPortal\lib\UnityHTTPD;

if (CONFIG["site"]["enable_exception_handler"]) {
    set_exception_handler(["UnityWebPortal\lib\UnityHTTPD", "exceptionHandler"]);
}

session_start();

if (isset($GLOBALS["ldapconn"])) {
    $LDAP = $GLOBALS["ldapconn"];
} else {
    $LDAP = new UnityLDAP();
    $GLOBALS["ldapconn"] = $LDAP;
}
$SQL = new UnitySQL();
$MAILER = new UnityMailer();
$WEBHOOK = new UnityWebhook();
$GITHUB = new UnityGithub();

if (isset($_SERVER["REMOTE_USER"])) {
    // Check if SSO is enabled on this page
    $SSO = UnitySSO::getSSO();
    $_SESSION["SSO"] = $SSO;

    $OPERATOR = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER, $WEBHOOK);
    $_SESSION["is_admin"] = $OPERATOR->isAdmin();

    if (isset($_SESSION["viewUser"]) && $_SESSION["is_admin"]) {
        $USER = new UnityUser($_SESSION["viewUser"], $LDAP, $SQL, $MAILER, $WEBHOOK);
    } else {
        $USER = $OPERATOR;
    }

    $_SESSION["user_exists"] = $USER->exists();
    $_SESSION["is_pi"] = $USER->isPI();
    $SEND_PIMESG_TO_ADMINS = CONFIG["mail"]["send_pimesg_to_admins"];

    $SQL->addLog($OPERATOR->uid, $_SERVER["REMOTE_ADDR"], "user_login", $OPERATOR->uid);
}

$LOC_HEADER = __DIR__ . "/templates/header.php";
$LOC_FOOTER = __DIR__ . "/templates/footer.php";
