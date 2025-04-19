<?php
require __DIR__ . "/../../../resources/autoload.php";
use UnityWebPortal\lib\UnitySite;

echo UnitySite::testValidSSHKey($_POST["key"]) ? "true" : "false";
