<?php

require_once "../../../resources/autoload.php";

if (!$USER->isAdmin()) {
    throw new Exception("not an admin");
}

if (!isset($_GET["pageid"])) {
    throw new Exception("Pageid not found");
}

$page = $SQL->getPage($_GET["pageid"]);
echo $page["content"];
