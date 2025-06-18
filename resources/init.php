<?php

/**
 * init.php - Initialization script that is run on every page of Unity
 */

use UnityWebPortal\lib\UnityConfig;
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

//
// Initialize Session
//
session_start();

//
// Config INIT
//
$CONFIG = UnityConfig::getConfig(__DIR__ . "/../defaults", __DIR__ . "/../deployment");

//
// Service Init
//

// Creates REDIS service
$REDIS = new UnityRedis(
    $CONFIG["redis"]["host"] ?? "",
    $CONFIG["redis"]["port"] ?? ""
);

// Creates LDAP service
$LDAP = new UnityLDAP(
    $CONFIG["ldap"]["uri"],
    $CONFIG["ldap"]["user"],
    $CONFIG["ldap"]["pass"],
    __DIR__ . "/../deployment/custom_user_mappings",
    $CONFIG["ldap"]["user_ou"],
    $CONFIG["ldap"]["group_ou"],
    $CONFIG["ldap"]["pigroup_ou"],
    $CONFIG["ldap"]["orggroup_ou"],
    $CONFIG["ldap"]["admin_group"],
    $CONFIG["ldap"]["user_group"],
    $CONFIG["ldap"]["def_user_shell"]
);

// Creates SQL service
$SQL = new UnitySQL(
    $CONFIG["sql"]["host"],
    $CONFIG["sql"]["dbname"],
    $CONFIG["sql"]["user"],
    $CONFIG["sql"]["pass"]
);

// Creates SMTP service
$MAILER = new UnityMailer(
    __DIR__ . "/mail",
    __DIR__ . "/../deployment/mail_overrides",
    $CONFIG["smtp"]["host"],
    $CONFIG["smtp"]["port"],
    $CONFIG["smtp"]["security"],
    $CONFIG["smtp"]["user"],
    $CONFIG["smtp"]["pass"],
    $CONFIG["smtp"]["ssl_verify"],
    $CONFIG["site"]["url"] . $CONFIG["site"]["prefix"],
    $CONFIG["mail"]["sender"],
    $CONFIG["mail"]["sender_name"],
    $CONFIG["mail"]["support"],
    $CONFIG["mail"]["support_name"],
    $CONFIG["mail"]["admin"],
    $CONFIG["mail"]["admin_name"],
    $CONFIG["mail"]["pi_approve"],
    $CONFIG["mail"]["pi_approve_name"]
);

// Creates Webhook service
$WEBHOOK = new UnityWebhook(
    __DIR__ . "/mail",
    __DIR__ . "/../deployment/mail_overrides",
    $CONFIG["webhook"]["url"],
    $CONFIG["site"]["url"] . $CONFIG["site"]["prefix"]
);

$GITHUB = new UnityGithub();

//
// SSO Init
//

try {
    $SSO = UnitySSO::getSSO();
} catch (SSOException $e) {
    $errorid = uniqid("sso-");
    $eppn = $_SERVER["REMOTE_USER"];
    UnitySite::errorLog("SSO Failure", "{$e} ($errorid)");
    UnitySite::die("Invalid eppn: '$eppn'. Please contact {$CONFIG["mail"]["support"]} (id: $errorid)", true);
}
if (!is_null($SSO)) {
    // SSO is available
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
    $SEND_PIMESG_TO_ADMINS = $CONFIG["mail"]["send_pimesg_to_admins"];

    $SQL->addLog(
        $OPERATOR->getUID(),
        $_SERVER['REMOTE_ADDR'],
        "user_login",
        $OPERATOR->getUID()
    );

    if (!$_SESSION["user_exists"]) {
        // populate cache
        $REDIS->setCache($SSO["user"], "org", $SSO["org"]);
        $REDIS->setCache($SSO["user"], "firstname", $SSO["firstname"]);
        $REDIS->setCache($SSO["user"], "lastname", $SSO["lastname"]);
        $REDIS->setCache($SSO["user"], "mail", $SSO["mail"]);
    }
}

//
// Define vars
//

$LOC_HEADER = __DIR__ . "/templates/header.php";
$LOC_FOOTER = __DIR__ . "/templates/footer.php";
