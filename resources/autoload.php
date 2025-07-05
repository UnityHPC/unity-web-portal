<?php

/**
 * autoload.php - this is the first file that is loaded on every webroot php file
 */

// Load Composer Libs
require_once __DIR__ . "/../vendor/autoload.php";

// submodule
require_once __DIR__ . "/lib/phpopenldaper/src/PHPOpenLDAPer/LDAPEntry.php";
require_once __DIR__ . "/lib/phpopenldaper/src/PHPOpenLDAPer/LDAPConn.php";
require_once __DIR__ . "/lib/phpopenldaper/src/PHPOpenLDAPer/ObjectClass.php";
require_once __DIR__ . "/lib/phpopenldaper/src/PHPOpenLDAPer/AttributeNotFound.php";

// load libs
require_once __DIR__ . "/lib/UnityLDAP.php";
require_once __DIR__ . "/lib/UnityUser.php";
require_once __DIR__ . "/lib/UnityGroup.php";
require_once __DIR__ . "/lib/UnityOrg.php";
require_once __DIR__ . "/lib/UnitySQL.php";
require_once __DIR__ . "/lib/UnityMailer.php";
require_once __DIR__ . "/lib/UnitySSO.php";
require_once __DIR__ . "/lib/UnitySite.php";
require_once __DIR__ . "/lib/UnityConfig.php";
require_once __DIR__ . "/lib/UnityWebhook.php";
require_once __DIR__ . "/lib/UnityRedis.php";
require_once __DIR__ . "/lib/UnityGithub.php";
require_once __DIR__ . "/lib/UserEntry.php";
require_once __DIR__ . "/lib/GroupEntry.php";

// run init script
require __DIR__ . "/init.php";
