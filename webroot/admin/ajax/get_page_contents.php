<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;

if (!$USER->isAdmin()) {
    UnityHTTPD::forbidden("not an admin");
}

if (!isset($_GET["pageid"])) {
    UnityHTTPD::badRequest("Pageid not found");
}

$page = $SQL->getPage($_GET["pageid"]);
echo $page["content"];
