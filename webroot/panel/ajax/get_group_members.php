<?php

require_once "../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;

if (!isset($_GET["pi_uid"])) {
    die("PI UID not set");
}

$group = new UnityGroup($_GET["pi_uid"], $LDAP, $SQL, $MAILER);
$members = $group->getGroupMembers();

// verify that the user querying is actually in the group
$found = false;
foreach ($members as $member) {
    if ($member->getUID() == $USER->getUID()) {
        $found = true;
        break;
    }
}

if (!$found) {
    die();
}

$count = count($members);
foreach ($members as $key => $member) {
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
