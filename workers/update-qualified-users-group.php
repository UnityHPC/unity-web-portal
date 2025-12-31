#!/usr/bin/env php
<?php
include __DIR__ . "/init.php";
use Garden\Cli\Cli;

$cli = new Cli();
$cli->description("Add and remove users from the qualified user group.")->opt(
    "dry-run",
    "Print changes without actually changing anything.",
    false,
    "boolean",
);
$args = $cli->parse($argv, true);

$qualified_list_before = $LDAP->userFlagGroups["qualified"]->getMemberUIDs();
$qualified_list_after = $qualified_list_before;
$pi_groups_attributes = $LDAP->getAllNonDefunctPIGroupsAttributes(
    ["cn", "memberuid"],
    ["memberuid" => []],
);
$uid2gids = [];
foreach ($pi_groups_attributes as $attributes) {
    $gid = $attributes["cn"][0];
    foreach ($attributes["memberuid"] as $uid) {
        if (array_key_exists($uid, $uid2gids)) {
            array_push($uid2gids[$uid], $gid);
        } else {
            $uid2gids[$uid] = [$gid];
        }
    }
}
// remove users who don't exist in uid2gids
$qualified_list_after = array_filter(
    $qualified_list_after,
    fn($x) => array_key_exists($x, $uid2gids),
    ARRAY_FILTER_USE_KEY,
);
foreach ($uid2gids as $uid => $gids) {
    if (count($gids) === 0) {
        if (($i = array_search($uid, $qualified_list_after)) !== false) {
            unset($qualified_list_after[$i]);
        }
    } else {
        if (!in_array($uid, $qualified_list_after)) {
            array_push($qualified_list_after, $uid);
        }
    }
}
$qualified_list_after = array_values($qualified_list_after);
$users_added = array_values(array_diff($qualified_list_after, $qualified_list_before));
$users_removed = array_values(array_diff($qualified_list_before, $qualified_list_after));
echo jsonEncode(
    [
        "added" => $users_added,
        "removed" => $users_removed,
    ],
    JSON_PRETTY_PRINT,
) . "\n";

if ($args["dry-run"]) {
    echo "dry run, nothing doing.\n";
} else {
    $LDAP->userFlagGroups["qualified"]->overwriteMemberUIDs($qualified_list_after);
}

