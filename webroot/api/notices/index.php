<?php

header('Content-type: text/plain');

require_once "../../../resources/autoload.php";

if (isset($_GET["line_wrap"])) {
    $CHAR_WRAP = $_GET["line_wrap"];
} else {
    $CHAR_WRAP = 80;
}

$notices = $SQL->getNotices();
$jsonArray = [];
foreach ($notices as $notice) {
    $formattedNotice = [
        "title" => $notice["title"],
        "date" => date('m-d-Y', strtotime($notice["date"])),
        "message" => wordwrap($notice["message"], $CHAR_WRAP)
    ];
    $jsonArray[] = $formattedNotice;
}

$jsonOutput = json_encode($jsonArray, JSON_PRETTY_PRINT);
echo $jsonOutput;
