<?php

require_once "../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;

$pi_uid = $SITE->array_get_or_bad_request("pi_uid", $_GET);
$group = new UnityGroup($pi_uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
if (!$group->userExists($USER->getUID())){
    $SITE->bad_request("User is not in group they are trying to read members of");
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
