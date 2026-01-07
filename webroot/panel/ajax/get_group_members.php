<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;


$group = new UnityGroup($_GET["gid"], $LDAP, $SQL, $MAILER, $WEBHOOK);
if (!$group->memberUIDExists($USER->uid)) {
    UnityHTTPD::forbidden("not a group member", "You are not a member of this group.");
}
$members = $group->getGroupMembersAttributes(["gecos", "mail"]);
$count = count($members);
$i = 0;
foreach ($members as $uid => $attributes) {
    if ($uid == $group->getOwner()->uid) {
        continue;
    }
    if ($i >= $count - 1) {
        echo "<tr class='expanded $i last'>";
    } else {
        echo "<tr class='expanded $i'>";
    }
    $fullname = $attributes["gecos"][0];
    $mail = $attributes["mail"][0];
    echo "<td>$fullname</td>";
    echo "<td>$uid</td>";
    echo "<td><a href='mailto:$mail'>$mail</a></td>";
    echo "<td><input type='hidden' name='uid' value='$uid'></td>";
    echo "</tr>";
    $i++;
}
