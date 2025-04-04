<?php

namespace UnityWebPortal\lib;

header('Content-type: text/plain');

require_once "../../../resources/autoload.php";

if (isset($_GET["line_wrap"])) {
    $CHAR_WRAP = $_GET["line_wrap"];
} else {
    $CHAR_WRAP = 80;
}

$content_name = $SITE->array_get_or_bad_request("content_name", $_GET);
try {
    echo $SQL->getPage($content_name)["content"];
} catch (UnitySQLRecordNotFoundException $e){
    $SITE->bad_request("page '$content_name' not found");
} catch (UnitySQLRecordNotUniqueException $e){
    $SITE->bad_request("page id '$content_name' not unique");
}
