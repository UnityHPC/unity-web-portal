<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnityHTTPDMessageLevel;

$level_str = base64_decode(UnityHTTPD::getPostData("level"));
$level = UnityHTTPDMessageLevel::from($level_str);
$title = base64_decode(UnityHTTPD::getPostData("title"));
$body = base64_decode(UnityHTTPD::getPostData("body"));
UnityHTTPD::deleteMessage($level, $title, $body);
