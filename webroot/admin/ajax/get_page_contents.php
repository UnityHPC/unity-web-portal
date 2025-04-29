<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;

if (!$USER->isAdmin()) {
    UnitySite::die();
}

if (!isset($_GET["pageid"])) {
    UnitySite::badRequest("Pageid not found");
}

$page = $SQL->getPage($_GET["pageid"]);
echo $page["content"];
