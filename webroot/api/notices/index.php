<?php

header('Content-type: text/plain');

require_once "../../../resources/autoload.php";

$notices = $SQL->getNotices();
$jsonArray = [];
foreach ($notices as $notice) {
    $formattedNotice = [
        "title" => $notice["title"],
        "date" => date('m-d-Y', strtotime($notice["date"])),
        "message" => $notice["message"]
    ];
    $jsonArray[] = $formattedNotice;
}

$jsonOutput = json_encode($jsonArray, JSON_PRETTY_PRINT);
echo $jsonOutput;
