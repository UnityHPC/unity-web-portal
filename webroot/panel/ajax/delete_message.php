<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnityHTTPDMessageLevel;

$level_str = UnityHTTPD::getPostData("level");
$level = UnityHTTPDMessageLevel::from($level_str);
$title = UnityHTTPD::getPostData("title");
$body = UnityHTTPD::getPostData("body");
UnityHTTPD::deleteMessage($level, $title, $body);
