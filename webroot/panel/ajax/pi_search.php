<?php

require_once __DIR__ . "/../../../resources/autoload.php";  // Load required libs

use UnityWebPortal\lib\UnityHTTPD;

$search_query = UnityHTTPD::getQueryParameter("search");
if (empty($search_query)) {
    echo "[]";
    UnityHTTPD::die();
}
if (!array_key_exists("pi_group_gid_to_owner_gecos_and_mail", $_SESSION)) {
    throw new RuntimeException('$_SESSION["pi_group_gid_to_owner_gecos_and_mail"] does not exist!');
}
$pi_group_gid_to_owner_gecos_and_mail = $_SESSION["pi_group_gid_to_owner_gecos_and_mail"];
$out = array();
foreach ($pi_group_gid_to_owner_gecos_and_mail as $gid => [$gecos, $mail]) {
    $search_query = strtolower($search_query);
    $gecos = strtolower($gecos);
    $mail = strtolower($mail);
    if (str_contains($gid, $search_query) || str_contains($gecos, $search_query) || str_contains($mail, $search_query)) {
        array_push($out, $gid);
        if (count($out) >= 10) {
            break;
        }
    }
}
echo jsonEncode($out);
