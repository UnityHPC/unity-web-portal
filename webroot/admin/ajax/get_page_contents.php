<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;

if (!$USER->getModifier("admin")) {
    UnityHTTPD::forbidden("not an admin");
}

$pageid = UnityHTTPD::getQueryParameter("pageid");
$page = $SQL->getPage($pageid);
echo $page["content"];
