<?php
//* * This is the main autoload for the site. This file should be the FIRST thing loaded on every page

// helper functions
function redirect($destination)
{
  if ($_SERVER["PHP_SELF"] != $destination) {
    header("Location: $destination");
    die("Redirect failed, click <a href='$destination'>here</a> to continue.");
  }
}

// Load Composer Libs
require_once __DIR__ . "/../vendor/autoload.php";

// Load config file values
$CONFIG = parse_ini_file(__DIR__ . "/../config/config.ini", true);

// set relative path
if ($CONFIG["site"]["prefix"] == "") {
  define("REL_PATH", $_SERVER['REQUEST_URI']);
} else {
  define("REL_PATH", str_replace($CONFIG["site"]["prefix"], "", $_SERVER['REQUEST_URI']));
}

// Start Session
session_start();

// load libs
require_once __DIR__ . "/lib/UnityLDAP.php";
require_once __DIR__ . "/lib/UnityUser.php";
require_once __DIR__ . "/lib/UnityGroup.php";
require_once __DIR__ . "/lib/UnitySQL.php";
require_once __DIR__ . "/lib/UnityMailer.php";

// initialize branding
require_once __DIR__ . "/run/branding.php";

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
__DIR__ . "/mail",
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
$BRANDING["mail"]["support_name"]
);

require_once __DIR__ . "/run/sso.php";

define("LOC_HEADER", __DIR__ . "/templates/header.php");
define("LOC_FOOTER", __DIR__ . "/templates/footer.php");

require_once "locale/en.php";  // Loads the locale class
