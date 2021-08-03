<?php

require "../../../resources/autoload.php";

if (!isset($_GET["pi_uid"])) {
    die("PI UID not set");
}

$group = new unityAccount($_GET["pi_uid"], $ldap, $sql, $sacctmgr, $storage);
$members = $group->getGroupMembers();

// this doesnt work right now???
//if (!in_array($user, $members)) {
//    die("You are not allowed to query this group");
//}
$count = count($members);
foreach ($members as $key=>$member) {
    if ($key >= $count - 1) {
        echo "<tr class='expanded $key last'>";
    } else {
        echo "<tr class='expanded $key'>";
    }

    echo "<td>" . $member->getFullname() . "</td>";
    echo "<td>" . $member->getUID() . "</td>";
    echo "<td><a href='mailto:" . $member->getMail() . "'>" . $member->getMail() . "</a></td>";
    echo "<td><input type='hidden' name='uid' value='" . $member->getUID() . "'></td>";
    echo "</tr>";
}