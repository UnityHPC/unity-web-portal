<?php

require_once __DIR__ . "/../../../vendor/autoload.php";
require_once __DIR__ . "/../../../resources/lib/utils.php";
require_once __DIR__ . "/../../../resources/lib/UnityHTTPD.php";

use UnityWebPortal\lib\UnityHTTPD;

header('Content-Type: application/json; charset=utf-8');
echo jsonEncode(["is_valid" => testValidSSHKey(UnityHTTPD::getPostData("key"))]);
