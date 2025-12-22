<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;


$gid = UnityHTTPD::getQueryParameter("gid");
$group = new UnityGroup($gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
if (!$group->memberUIDExists($USER->uid)) {
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
    $uid_escaped = htmlspecialchars($uid);
    $gecos = htmlspecialchars($attributes["gecos"][0]);
    $mail_link = "mailto:" . urlencode($attributes["mail"][0]);
    $mail_display = htmlspecialchars($attributes["mail"][0]);
    echo "<td>$gecos</td>";
    echo "<td>$uid_escaped</td>";
    echo "<td><a href='$mail_link'>$mail_display</a></td>";
    echo "<td><input type='hidden' name='uid' value='$uid_escaped'></td>";
    echo "</tr>";
    $i++;
}
