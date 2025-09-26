<?php

require_once __DIR__ . "/../../../vendor/autoload.php";

echo testValidSSHKey($_POST["key"]) ? "true" : "false";
