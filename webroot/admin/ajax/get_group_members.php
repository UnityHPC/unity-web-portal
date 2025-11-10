<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;

if (!$USER->isAdmin()) {
    UnityHTTPD::forbidden("not an admin");
}

if (!isset($_GET["gid"])) {
    UnityHTTPD::badRequest("PI UID not set");
}

$group = new UnityGroup($_GET["gid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
$members = $group->getGroupMembers();
$requests = $group->getRequests();

$i = 0;
$count = count($members) + count($requests);
foreach ($members as $member) {
    if ($member->uid == $group->getOwner()->uid) {
        continue;
    }

    if ($i >= $count - 1) {
        echo "<tr class='expanded $i last'>";
    } else {
        echo "<tr class='expanded $i'>";
    }

    echo "<td>" . $member->getFullname() . "</td>";
    echo "<td>" . $member->uid . "</td>";
    echo "<td><a href='mailto:" . $member->getMail() . "'>" . $member->getMail() . "</a></td>";
    echo "<td>";
    echo "
        <form
            action=''
            method='POST'
            onsubmit='
                return confirm(\"Are you sure you want to remove $member->uid from this group?\");
            '
        >
        <input type='hidden' name='form_type' value='remUserChild'>
        <input type='hidden' name='uid' value='" . $member->uid . "'>
        <input type='hidden' name='pi' value='" . $group->gid . "'>
        <input type='submit' value='Remove'>
        </form>
    ";
    echo "</td>";
    echo "</tr>";

    $i++;
}

foreach ($requests as $i => [$user, $timestamp]) {
    if ($i >= $count - 1) {
        echo "<tr class='expanded $i last'>";
    } else {
        echo "<tr class='expanded $i'>";
    }
    $name = $user->getFullName();
    $email = $user->getMail();
    echo "<td>$name</td>";
    echo "<td>$user->uid</td>";
    echo "<td><a href='mailto:$email'>$email</a></td>";
    echo "<td>";
    echo
        "<form action='' method='POST'
    onsubmit='return confirm(\"Are you sure you want to approve $user->uid ?\");'>
    <input type='hidden' name='form_type' value='reqChild'>
    <input type='hidden' name='uid' value='$user->uid'>
    <input type='hidden' name='pi' value='$group->gid'>
    <input type='submit' name='action' value='Approve'>
    <input type='submit' name='action' value='Deny'></form>";
    echo "</td>";
    echo "</tr>";

    $i++;
}
