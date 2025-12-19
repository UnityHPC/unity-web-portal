<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnityHTTPDMessageLevel;

$level_str = base64_decode($_POST["level"]);
$level = UnityHTTPDMessageLevel::from($level_str);
$title = base64_decode($_POST["title"]);
$body = base64_decode($_POST["body"]);
UnityHTTPD::deleteMessage($level, $title, $body);
