<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnityHTTPDMessageLevel;

$level_str = UnityHTTPD::getPostData("level");
$level = UnityHTTPDMessageLevel::from($level_str);
$title_regex = UnityHTTPD::getPostData("title_regex");
$body_regex = UnityHTTPD::getPostData("body_regex");
UnityHTTPD::deleteMessage($level, $title_regex, $body_regex);
