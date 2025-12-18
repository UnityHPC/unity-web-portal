<?php

require_once __DIR__ . "/../../../vendor/autoload.php";
require_once __DIR__ . "/../../../resources/lib/utils.php";
require_once __DIR__ . "/../../../resources/lib/UnityHTTPD.php";

use UnityWebPortal\lib\UnityHTTPD;

header('Content-Type: application/json; charset=utf-8');
[$is_valid, $explanation] = testValidSSHKey(UnityHTTPD::getPostData("key"));
echo jsonEncode(["is_valid" => $is_valid, "explanation" => $explanation]);
