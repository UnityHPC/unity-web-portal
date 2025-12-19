<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UserFlag;

if (!$USER->getFlag(UserFlag::ADMIN)) {
    UnityHTTPD::forbidden("not an admin", "You are not an admin.");
}

$page = $SQL->getPage($_GET["pageid"]);
header('Content-Type: application/json; charset=utf-8');
echo jsonEncode(["content" => $page["content"]]);
