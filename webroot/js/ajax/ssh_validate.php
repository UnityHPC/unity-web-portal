<?php

require_once __DIR__ . "/../../../resources/lib/UnitySite.php";

echo (new UnityWebPortal\lib\UnitySite())->testValidSSHKey($_POST["key"]) ? "true" : "false";
