<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;

[$is_valid, $explanation] = testValidSSHKey(UnityHTTPD::getPostData("key"));
header('Content-Type: application/json; charset=utf-8');
echo jsonEncode(["is_valid" => $is_valid, "explanation" => $explanation]);
UnityHTTPD::die();
