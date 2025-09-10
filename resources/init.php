<?php

/**
 * init.php - Initialization script that is run on every page of Unity
 */

use UnityWebPortal\lib\UnityLDAP;
use UnityWebPortal\lib\UnityMailer;
use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UnitySSO;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityRedis;
use UnityWebPortal\lib\UnityWebhook;
use UnityWebPortal\lib\UnityGithub;
use UnityWebPortal\lib\UnitySite;
use UnityWebPortal\lib\exceptions\SSOException;

session_start();

$REDIS = new UnityRedis();

if (isset($GLOBALS["ldapconn"])) {
    $LDAP = $GLOBALS["ldapconn"];
} else {
    $LDAP = new UnityLDAP();
    $GLOBALS["ldapconn"] = $LDAP;
}

$SQL = new UnitySQL();

$MAILER = new UnityMailer(
    __DIR__ . "/mail",
    __DIR__ . "/../deployment/mail_overrides",
    CONFIG["smtp"]["host"],
    CONFIG["smtp"]["port"],
    CONFIG["smtp"]["security"],
    CONFIG["smtp"]["user"],
    CONFIG["smtp"]["pass"],
    CONFIG["smtp"]["ssl_verify"],
    CONFIG["site"]["url"] . CONFIG["site"]["prefix"],
    CONFIG["mail"]["sender"],
    CONFIG["mail"]["sender_name"],
    CONFIG["mail"]["support"],
    CONFIG["mail"]["support_name"],
    CONFIG["mail"]["admin"],
    CONFIG["mail"]["admin_name"],
    CONFIG["mail"]["pi_approve"],
    CONFIG["mail"]["pi_approve_name"]
);

$WEBHOOK = new UnityWebhook(
    __DIR__ . "/mail",
    __DIR__ . "/../deployment/mail_overrides",
    CONFIG["webhook"]["url"],
    CONFIG["site"]["url"] . CONFIG["site"]["prefix"]
);

$GITHUB = new UnityGithub();

if (isset($_SERVER["REMOTE_USER"])) {  // Check if SSO is enabled on this page
    try {
        $SSO = UnitySSO::getSSO();
    } catch (SSOException $e) {
        $errorid = uniqid("sso-");
        $eppn = $_SERVER["REMOTE_USER"];
        UnitySite::errorLog("SSO Failure", "{$e} ($errorid)");
        UnitySite::die(
            "Invalid eppn: '$eppn'. Please contact support at "
                . CONFIG["mail"]["support"]
                . " (id: $errorid)",
            true
        );
    }
    $_SESSION["SSO"] = $SSO;

    $OPERATOR = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    $_SESSION["is_admin"] = $OPERATOR->isAdmin();

    if (isset($_SESSION["viewUser"]) && $_SESSION["is_admin"]) {
        $USER = new UnityUser($_SESSION["viewUser"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    } else {
        $USER = $OPERATOR;
    }

    $_SESSION["user_exists"] = $USER->exists();
    $_SESSION["is_pi"] = $USER->isPI();
    $SEND_PIMESG_TO_ADMINS = CONFIG["mail"]["send_pimesg_to_admins"];

    $SQL->addLog(
        $OPERATOR->uid,
        $_SERVER['REMOTE_ADDR'],
        "user_login",
        $OPERATOR->uid
    );

    if (!$_SESSION["user_exists"]) {
        // populate cache
        $REDIS->setCache($SSO["user"], "org", $SSO["org"]);
        $REDIS->setCache($SSO["user"], "firstname", $SSO["firstname"]);
        $REDIS->setCache($SSO["user"], "lastname", $SSO["lastname"]);
        $REDIS->setCache($SSO["user"], "mail", $SSO["mail"]);
    }
}

$LOC_HEADER = __DIR__ . "/templates/header.php";
$LOC_FOOTER = __DIR__ . "/templates/footer.php";
