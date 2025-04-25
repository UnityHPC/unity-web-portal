<?php

require_once __DIR__ . "/../../../resources/autoload.php";

echo $SITE->testValidSSHKey($_POST["key"]) ? "true" : "false";
