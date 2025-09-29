<?php

require_once __DIR__ . "/../../../vendor/autoload.php";
require_once __DIR__ . "/../../../resources/lib/utils.php";

echo testValidSSHKey($_POST["key"]) ? "true" : "false";
