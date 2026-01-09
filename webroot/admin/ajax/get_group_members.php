<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UserFlag;

if (!$USER->getFlag(UserFlag::ADMIN)) {
    UnityHTTPD::forbidden("not an admin", "You are not an admin.");
}

$gid = UnityHTTPD::getQueryParameter("gid");
$group = new UnityGroup($gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
$members = $group->getGroupMembersAttributes(["gecos", "mail"]);
$requests = $group->getRequests();

$CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();

$i = 0;
$count = count($members) + count($requests);
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
    echo "<td>";
    echo "
        <form
            action=''
            method='POST'
            onsubmit='
                return confirm(\"Are you sure you want to remove $uid from this group?\");
            '
        >
        $CSRFTokenHiddenFormInput
        <input type='hidden' name='form_type' value='remUserChild'>
        <input type='hidden' name='uid' value='$uid'>
        <input type='hidden' name='pi' value='$group->gid'>
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
    $CSRFTokenHiddenFormInput
    <input type='hidden' name='form_type' value='reqChild'>
    <input type='hidden' name='uid' value='$user->uid'>
    <input type='hidden' name='pi' value='$group->gid'>
    <input type='submit' name='action' value='Approve'>
    <input type='submit' name='action' value='Deny'></form>";
    echo "</td>";
    echo "</tr>";
    $i++;
}
