<?php
//* * This is the main autoload for the site. This file should be the FIRST thing loaded on every page

// Load Composer Libs
require_once __DIR__ . "/../vendor/autoload.php";

// Load config file values
$CONFIG = parse_ini_file(__DIR__ . "/../config/config.ini", true);

// Load unity logger program
require_once __DIR__ . "/lib/UnityLogger.php";
$LOGGER = new UnityLogger($CONFIG["site"]["log_path"], false);

// set relative path
if ($CONFIG["site"]["prefix"] == "") {
  define("REL_PATH", $_SERVER['REQUEST_URI']);
} else {
  define("REL_PATH", str_replace($CONFIG["site"]["prefix"], "", $_SERVER['REQUEST_URI']));
}

// Start Session
session_start();

// load libs
require_once __DIR__ . "/lib/globals.php";
require_once __DIR__ . "/lib/UnityLDAP.php";
require_once __DIR__ . "/lib/UnityUser.php";
require_once __DIR__ . "/lib/UnityGroup.php";
require_once __DIR__ . "/lib/UnitySQL.php";
require_once __DIR__ . "/lib/UnityMailer.php";

// Loading branding
$branding_file_loc = __DIR__ . "/../config/branding";
$BRANDING = parse_ini_file($branding_file_loc . "/config.ini", true);

define("DOMAIN", $_SERVER['HTTP_HOST']);
$branding_override = $branding_file_loc . "/overrides/" . DOMAIN . ".ini";
if (file_exists($branding_override)) {
    $override_config = parse_ini_file($branding_override);
    $BRANDING = array_merge($BRANDING, $override_config);
}

//
// Initialize Service Stack
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
"templates/mail",
$CONFIG["smtp"]["host"],
$CONFIG["smtp"]["port"],
$CONFIG["smtp"]["security"],
$CONFIG["smtp"]["user"],
$CONFIG["smtp"]["pass"],
$CONFIG["smtp"]["ssl_verify"]
);

$LOGGER->logInfo("Accepted connection from " . $_SERVER['REMOTE_ADDR']);

if (isset($_SERVER["REMOTE_USER"])) {  // Check if SSO is enabled on this page
  // Set SSO Session Vars - Vars stored in session to be accessible outside shib-controlled areas of the sites (ie contact page)
  $SSO = array(
    "user" => EPPN_to_uid($_SERVER["REMOTE_USER"]),
    "firstname" => $_SERVER["givenName"],
    "lastname" => $_SERVER["sn"],
    "name" => $_SERVER["givenName"] . " " . $_SERVER["sn"],
    "mail" => isset($_SERVER["mail"]) ? $_SERVER["mail"] : $_SERVER["eppn"]  // Fallback to EPPN if mail is not set
  );
  $_SESSION["SSO"] = $SSO;  // Set the session var for non-authenticated pages

  // define user object
  $USER = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER);
  $_SESSION["user_exists"] = $USER->exists();
  $_SESSION["is_pi"] = $USER->isPI();
  $_SESSION["is_admin"] = $USER->isAdmin();
}

// TODO FIX THIS
define("LOC_HEADER", __DIR__ . "/templates/header.php");
define("LOC_FOOTER", __DIR__ . "/templates/footer.php");

require_once "locale/en.php";  // Loads the locale class
