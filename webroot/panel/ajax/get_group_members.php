<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;

if (!isset($_GET["gid"])) {
    UnityHTTPD::badRequest("PI UID not set");
}

$group = new UnityGroup($_GET["gid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
if (!$group->memberExists($USER)) {
    UnityHTTPD::forbidden("not a group member");
}
$members = $group->getGroupMembersAttributes(["gecos", "mail"]);
$count = count($members);
foreach ($members as $uid => $attributes) {
    if ($uid == $group->getOwner()->uid) {
        continue;
    }

    if ($key >= $count - 1) {
        echo "<tr class='expanded $key last'>";
    } else {
        echo "<tr class='expanded $key'>";
    }
    $fullname = $attributes["gecos"][0];
    $mail = $attributes["mail"][0];
    echo "<td>$fullname</td>";
    echo "<td>$uid</td>";
    echo "<td><a href='mailto:$mail'>$mail</a></td>";
    echo "<td><input type='hidden' name='uid' value='$uid'></td>";
    echo "</tr>";
}
