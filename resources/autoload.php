<?php
//* * This is the main autoload for the site. This file should be the FIRST thing loaded on every page

if (file_exists("config.php")) {
  die("Config file config.php not found!");
} else {
  require_once "config.php";
}

require "libraries/composer/vendor/autoload.php";

require_once config::PATHS["libraries"] . "/unity-logger.php";
$LOGGER = new unityLogger(config::LOG_PATH, false);

// set relative path
if (config::PREFIX == "/") {
  define("REL_PATH", $_SERVER['REQUEST_URI']);
} else {
  define("REL_PATH", str_replace(config::PREFIX, "", $_SERVER['REQUEST_URI']));
}

// Start Session
session_start();

// DEBUG LOCK
if (config::MAINTENANCE_MODE && !isset($_SESSION["maint"]) && !(strpos($_SERVER["REQUEST_URI"], "/panel-auth.php") !== false)) {
  die("The Unity Cluster website is undergoing maintenance. The JupyterHub portal is available <a href='/panel/jhub'>here</a>");
}

require_once "branding/branding.php";
$BRANDING = new branding();

require_once config::PATHS["templates"] . "/globals.php";

require_once config::PATHS["libraries"] . "/slurm.php";
require_once config::PATHS["libraries"] . "/unity-ldap.php";
require_once config::PATHS["libraries"] . "/unity-user.php";
require_once config::PATHS["libraries"] . "/unity-account.php";
require_once config::PATHS["libraries"] . "/unity-sql.php";
require_once config::PATHS["libraries"] . "/template_mailer.php";

require_once config::PATHS["libraries"] . "/unity-service.php";

$SERVICE = new serviceStack($LOGGER);
$SERVICE->getLogger()->logInfo("Accepted connection from " . $_SERVER['REMOTE_ADDR']);

$SERVICE->add_ldap(config::LDAP);
$SERVICE->add_sql(config::SQL);
$SERVICE->add_mail(config::MAIL);
$SERVICE->add_sacctmgr(config::SLURM);

if (isset($_SERVER["REMOTE_USER"])) {  // Check if SHIB is enabled on this page
  // Set Shibboleth Session Vars - Vars stored in session to be accessible outside shib-controlled areas of the sites (ie contact page)
  $SHIB = array(
    "netid" => EPPN_to_uid($_SERVER["REMOTE_USER"]),
    "firstname" => $_SERVER["givenName"],
    "lastname" => $_SERVER["sn"],
    "name" => $_SERVER["givenName"] . " " . $_SERVER["sn"],
    "mail" => isset($_SERVER["mail"]) ? $_SERVER["mail"] : $_SERVER["eppn"]  // Fallback to EPPN if mail is not set
  );
  $_SESSION["SHIB"] = $SHIB;  // Set the session var for non-authenticated pages

  $USER = new unityUser($SHIB["netid"], $SERVICE);
  $_SESSION["user_exists"] = $USER->exists();
  $_SESSION["is_pi"] = $USER->isPI();
  $_SESSION["is_admin"] = $USER->isAdmin();
} elseif (DEVMODE) {
  // dev environment enabled, now we need to check if the user is currently in /panel, which is the only place remote_user would be set
  $panelSearch = "/panel";
  if (substr(REL_PATH, 0, strlen($panelSearch)) === $panelSearch) {
    $SHIB = array(
      "netid" => EPPN_to_uid(DEVUSER["eppn"]),
      "firstname" => DEVUSER["firstname"],
      "lastname" => DEVUSER["lastname"],
      "name" => DEVUSER["firstname"] . " " . DEVUSER["lastname"],
      "mail" => DEVUSER["mail"]
    );
    $_SESSION["SHIB"] = $SHIB;

    $USER = new unityUser($SHIB["netid"], $SERVICE);
    $_SESSION["user_exists"] = $USER->exists();
    $_SESSION["is_pi"] = $USER->isPI();
    $_SESSION["is_admin"] = $USER->isAdmin();
  }
}

// Load Locale
require_once config::PATHS["locale"] . "/" . config::LOCALE . ".php";  // Loads the locale class
