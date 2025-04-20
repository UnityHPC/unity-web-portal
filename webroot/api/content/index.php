<?php

header('Content-type: text/plain');

require_once __DIR__ . "/../../../resources/autoload.php";

if (isset($_GET["line_wrap"])) {
    $CHAR_WRAP = $_GET["line_wrap"];
} else {
    $CHAR_WRAP = 80;
}

if (!isset($_GET["content_name"])) {
    die();
}

echo $SQL->getPage($_GET["content_name"])["content"];
