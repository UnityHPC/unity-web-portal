<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;


$gid = UnityHTTPD::getQueryParameter("gid");
$group = new UnityGroup($gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
if (!$group->memberExists($USER->uid)) {
    UnityHTTPD::forbidden("not a group member");
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
