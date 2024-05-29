<?php

require_once "../../../resources/autoload.php";

if (!isset($_GET["group_name"])) {
    die();
}

$group_name = $_GET["group_name"];
$is_available = $USER->checkGroupName($group_name);

echo $is_available;
