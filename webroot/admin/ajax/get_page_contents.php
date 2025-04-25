<?php

require_once __DIR__ . "/../../../resources/autoload.php";

if (!$USER->isAdmin()) {
    throw new Exception("access denied");
}

if (!isset($_GET["pageid"])) {
    throw new Exception("Pageid not defined");
}

$page = $SQL->getPage($_GET["pageid"]);
echo $page["content"];
