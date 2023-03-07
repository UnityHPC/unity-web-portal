<?php

/**
 * init.php - Initialization script that is run on every page of Unity
 */

use UnityWebPortal\lib\{
    UnityBranding,
    UnitySite,
    UnityLDAP,
    UnityMailer,
    UnitySQL,
    UnitySSO,
    UnityUser
};

//
// Initialize Session
//
session_start();

//
// Config INIT
//
$CONFIG = UnitySite::getConfig(__DIR__ . "/../config");
$BRANDING = UnityBranding::getBranding(__DIR__ . "/../config/branding");

//
// Service Init
//

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
    $BRANDING["site"]["url"] . $CONFIG["site"]["prefix"],
    $BRANDING["mail"]["sender"],
    $BRANDING["mail"]["sender_name"],
    $BRANDING["mail"]["support"],
    $BRANDING["mail"]["support_name"],
    $BRANDING["mail"]["admin"],
    $BRANDING["mail"]["admin_name"],
    $BRANDING["mail"]["pi_approve"],
    $BRANDING["mail"]["pi_approve_name"]
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

    $USER = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER);
    $_SESSION["is_admin"] = $USER->isAdmin();

    if (isset($_SESSION["viewUser"]) && $_SESSION["is_admin"]) {
        $USER = new UnityUser($_SESSION["viewUser"], $LDAP, $SQL, $MAILER);
    }

    $_SESSION["user_exists"] = $USER->exists();
    $_SESSION["is_pi"] = $USER->isPI();
}

//
// Define vars
//

$LOC_HEADER = __DIR__ . "/templates/header.php";
$LOC_FOOTER = __DIR__ . "/templates/footer.php";
