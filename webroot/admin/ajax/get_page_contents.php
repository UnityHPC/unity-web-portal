<?php

require_once "../../../resources/autoload.php";

if (!$USER->isAdmin()) {
    $SITE->forbidden($SQL, $USER);
}
$page_id = $SITE->array_get_or_bad_request("page_id", $_GET);
try {
    $page = $SQL->getPage($_GET["pageid"]);
    echo $page["content"];
} catch (UnitySQLRecordNotFoundException $e) {
    $SITE->bad_request("page '$page_id' not found");
} catch (UnitySQLRecordNotUniqueException $e){
    $SITE->bad_request("page id'$page_id' not unique");
}
