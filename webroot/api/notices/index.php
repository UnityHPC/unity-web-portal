<?php

header('Content-type: text/plain');

require_once "../../../resources/autoload.php";

if (isset($_GET["line_wrap"])) {
    $CHAR_WRAP = $_GET["line_wrap"];
} else {
    $CHAR_WRAP = 80;
}

$notices = $SQL->getNotices();
foreach ($notices as $notice) {
    echo $notice["title"] . "\r\n";
    echo date('m-d-Y', strtotime($notice["date"])) . "\r\n";

    $lineArr = explode("\r\n", wordwrap($notice["message"], $CHAR_WRAP));
    foreach ($lineArr as $line) {
        echo $line;
    }

    echo "\r\n\r\n";
}
