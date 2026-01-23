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
use UnityWebPortal\lib\UserFlag;

if (CONFIG["site"]["enable_exception_handler"]) {
    set_exception_handler(["UnityWebPortal\lib\UnityHTTPD", "exceptionHandler"]);
}

if (CONFIG["site"]["enable_error_handler"]) {
    set_error_handler(["UnityWebPortal\lib\UnityHTTPD", "errorHandler"]);
}

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

session_start();
// https://stackoverflow.com/a/1270960/18696276
if (time() - ($_SESSION["LAST_ACTIVITY"] ?? 0) > CONFIG["site"]["session_cleanup_idle_seconds"]) {
    $_SESSION["csrf_tokens"] = [];
    $_SESSION["messages"] = [];
    if (array_key_exists("pi_group_gid_to_owner_gecos_and_mail", $_SESSION)) {
        unset($_SESSION["pi_group_gid_to_owner_gecos_and_mail"]);
    }
    session_write_close();
    session_start();
}
$_SESSION["LAST_ACTIVITY"] = time();

if (!array_key_exists("messages", $_SESSION)) {
    $_SESSION["messages"] = [];
}

if (!array_key_exists("csrf_tokens", $_SESSION)) {
    $_SESSION["csrf_tokens"] = [];
}

if (isset($_SERVER["REMOTE_USER"])) {
    _setcookie("navbar_show_logged_in_user_pages", "true");
    $SSO = UnitySSO::getSSO();
    $_SESSION["OPERATOR"] = $SSO["user"];
    $_SESSION["OPERATOR_IP"] = $_SERVER["REMOTE_ADDR"];
    if (
        isset($_SESSION["viewUser"]) &&
        $LDAP->userFlagGroups["admin"]->memberUIDExists($SSO["user"])
    ) {
        $USER = new UnityUser($_SESSION["viewUser"], $LDAP, $SQL, $MAILER, $WEBHOOK);
    } else {
        $USER = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER, $WEBHOOK);
    }
    _setcookie("navbar_show_admin_pages", $USER->getFlag(UserFlag::ADMIN) ? "true" : "false");
    _setcookie("navbar_show_pi_pages", $USER->isPI() ? "true" : "false");
    $SQL->addLog("user_login", $SSO["user"]);
    $USER->updateIsQualified(); // in case manual changes have been made to PI groups
} else {
    _setcookie("navbar_show_logged_in_user_pages", "false");
}
