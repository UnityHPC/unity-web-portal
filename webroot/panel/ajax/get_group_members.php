<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnitySite;

if (!isset($_GET["pi_uid"])) {
    UnitySite::badRequest("PI UID not set");
}

$group = new UnityGroup($_GET["pi_uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
$members = $group->getGroupMembers();

// verify that the user querying is actually in the group
$found = false;
foreach ($members as $member) {
    if ($member->getUID() == $USER->getUID()) {
        $found = true;
        break;
    }
}

if ($found) {
    UnitySite::forbidden("not a group member");
}

$count = count($members);
foreach ($members as $key => $member) {
    if ($member->getUID() == $group->getOwner()->getUID()) {
        continue;
    }

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
