<?php

require_once __DIR__ . "/../../../resources/lib/UnitySite.php";
use UnityWebPortal\lib\UnitySite;

echo UnitySite::testValidSSHKey($_POST["key"]) ? "true" : "false";
