<?php

require_once __DIR__ . "/../../../vendor/autoload.php";
require_once __DIR__ . "/../../../resources/lib/utils.php";
header('Content-Type: application/json; charset=utf-8');
echo jsonEncode(["is_valid" => testValidSSHKey($_POST["key"])]);
