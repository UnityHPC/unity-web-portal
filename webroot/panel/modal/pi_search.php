<?php

require_once "../../../resources/autoload.php";  // Load required libs

$search_query = $_GET["search"];  // Search is passed as a get var
if (empty($search_query)) {
    die("<span>No Results</span>");
}

$assocs = $LDAP->getAllPIGroups($SQL, $MAILER, $REDIS, $WEBHOOK);

$MAX_COUNT = 10;  // Max results of PI search

$out = array();
foreach ($assocs as $assoc_obj) {
    $assoc = $assoc_obj->getPIUID();
    // loop through each association
    if (strpos($assoc, $search_query) !== false) {
        array_push($out, $assoc);
        if (count($out) >= $MAX_COUNT) {
            break;
        }
    }
    $fn = strtolower($assoc_obj->getOwner()->getFullName());
    if (strpos($fn, strtolower($search_query)) !== false) {
        if (!in_array($assoc, $out)) {
               array_push($out, $assoc);
               if (count($out) >= $MAX_COUNT) {
                   break;
               }
       }
    }
}

foreach ($out as $pi_acct) {
    echo "<span>$pi_acct</span>";
}
