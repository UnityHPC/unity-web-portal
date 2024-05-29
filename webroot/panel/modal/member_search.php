<?php

require_once "../../../resources/autoload.php";  // Load required libs
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityUser;

$search_query = $_GET["search"];  // Search is passed as a get var
$group_id = $_GET["group"];
if (empty($search_query)) {
    die("<span>No Results</span>");
}

$group = new UnityGroup($_GET["group"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
$assocs = $group->getGroupMembers();

$MAX_COUNT = 10;  // Max results of PI search

$out = array();
foreach ($assocs as $assoc_obj) {
    $assoc = $assoc_obj->getUID();
    // loop through each association
    if (strpos($assoc, $search_query) !== false) {
        array_push($out, $assoc);
        if (count($out) >= $MAX_COUNT) {
            break;
        }
    }
}

foreach ($out as $pi_acct) {
    echo "<span>$pi_acct</span>";
}
