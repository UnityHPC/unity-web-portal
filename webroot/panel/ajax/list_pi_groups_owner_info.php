<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;

$owner_uids = $LDAP->getAllPIGroupOwnerUIDs();
$owner_attributes = $LDAP->getUsersAttributes(
    $owner_uids,
    ["uid", "gecos", "mail"],
    default_values: ["gecos" => ["(not found)"], "mail" => ["(not found)"]]
);
$output = [];
foreach ($owner_attributes as $attributes) {
    $gid = UnityGroup::ownerUID2GID($attributes["uid"][0]);
    $output[$gid] = ["gecos" => $attributes["gecos"][0], "mail" => $attributes["mail"][0]];
}
echo jsonEncode($output);
