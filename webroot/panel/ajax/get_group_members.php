<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnitySite;

if (!isset($_GET["gid"])) {
    UnitySite::badRequest("PI UID not set");
}

$group = new UnityGroup($_GET["gid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
if (!$group->userExists($USER)) {
    UnitySite::forbidden("not a group member");
}
$members = $group->getGroupMembers();
$count = count($members);
foreach ($members as $key => $member) {
    if ($member->uid == $group->getOwner()->uid) {
        continue;
    }

    if ($key >= $count - 1) {
        echo "<tr class='expanded $key last'>";
    } else {
        echo "<tr class='expanded $key'>";
    }

    echo "<td>" . $member->getFullname() . "</td>";
    echo "<td>" . $member->uid . "</td>";
    echo "<td><a href='mailto:" . $member->getMail() . "'>" . $member->getMail() . "</a></td>";
    echo "<td><input type='hidden' name='uid' value='" . $member->uid . "'></td>";
    echo "</tr>";
}
