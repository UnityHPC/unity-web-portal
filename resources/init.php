<?php

/**
 * init.php - Initialization script that is run on every page of Unity
 */

use UnityWebPortal\lib\{
    UnityConfig,
    UnityLDAP,
    UnityMailer,
    UnitySQL,
    UnitySSO,
    UnityUser,
    UnityRedis
};

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
    $CONFIG["redis"]["host"],
    $CONFIG["redis"]["port"]
);

// Creates LDAP service
$LDAP = new UnityLDAP(
    $CONFIG["ldap"]["uri"],
    $CONFIG["ldap"]["user"],
    $CONFIG["ldap"]["pass"],
    __DIR__ . "/../config/custom_user_mappings",
    $CONFIG["ldap"]["user_ou"],
    $CONFIG["ldap"]["group_ou"],
    $CONFIG["ldap"]["pigroup_ou"],
    $CONFIG["ldap"]["orggroup_ou"],
    $CONFIG["ldap"]["admin_group"],
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
    __DIR__ . "/../config/mail_overrides",
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

//
// SSO Init
//

$SSO = UnitySSO::getSSO();
if (!is_null($SSO)) {
    // SSO is available
    $_SESSION["SSO"] = $SSO;

// add sso login entry to mysql table
    $SQL->addSSOEntry(
        $SSO["user"],
        $SSO["org"],
        $SSO["firstname"],
        $SSO["lastname"],
        $SSO["mail"]
    );

    $USER = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER, $REDIS);
    $_SESSION["is_admin"] = $USER->isAdmin();

    if (isset($_SESSION["viewUser"]) && $_SESSION["is_admin"]) {
        $USER = new UnityUser($_SESSION["viewUser"], $LDAP, $SQL, $MAILER, $REDIS);
    }

    $_SESSION["user_exists"] = $USER->exists();
    $_SESSION["is_pi"] = $USER->isPI();
}

//
// Define vars
//

$LOC_HEADER = __DIR__ . "/templates/header.php";
$LOC_FOOTER = __DIR__ . "/templates/footer.php";
