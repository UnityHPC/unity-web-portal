<?php

require_once __DIR__ . "/../../../resources/lib/UnitySite.php";
require_once __DIR__ . "/../../../vendor/autoload.php";

echo UnityWebPortal\lib\UnitySite::testValidSSHKey($_POST["key"]) ? "true" : "false";
