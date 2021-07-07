<?php
//* * This is the main autoload for the site. This file should be the FIRST thing loaded on every page

if (file_exists("config.php")) {
  die("Config file config.php not found!");
} else {
  require_once "config.php";
}

// Start Session
session_start();

// DEBUG LOCK
if (config::MAINTENANCE_MODE && !isset($_SESSION["maint"]) && !(strpos($_SERVER["REQUEST_URI"], "/panel-auth.php") !== false)) {
  die("The Unity Cluster website is undergoing maintenance. The JupyterHub portal is available <a href='/panel/jhub'>here</a>");
}

require_once config::PATHS["templates"] . "/globals.php";

require_once config::PATHS["libraries"] . "/slurm.php";
require_once config::PATHS["libraries"] . "/unity-ldap.php";
require_once config::PATHS["libraries"] . "/unity-user.php";
require_once config::PATHS["libraries"] . "/unity-account.php";
require_once config::PATHS["libraries"] . "/unity-sql.php";
require_once config::PATHS["libraries"] . "/curl.php";
require_once config::PATHS["libraries"] . "/unity-storage.php";

require_once config::PATHS["init"] . "/slurm.php";
require_once config::PATHS["init"] . "/sql.php";
require_once config::PATHS["init"] . "/shib.php";
require_once config::PATHS["init"] . "/ldap.php";
require_once config::PATHS["init"] . "/smtp.php";
require_once config::PATHS["init"] . "/api.php";

// Load Locale
require_once config::PATHS["locale"] . "/" . config::LOCALE . ".php";  // Loads the locale class