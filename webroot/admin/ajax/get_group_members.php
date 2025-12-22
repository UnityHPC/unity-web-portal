<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UserFlag;

if (!$USER->getFlag(UserFlag::ADMIN)) {
    UnityHTTPD::forbidden("not an admin");
}

$gid = UnityHTTPD::getQueryParameter("gid");
$group = new UnityGroup($gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
$members = $group->getGroupMembersAttributes(["gecos", "mail"]);
$requests = $group->getRequests();

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
    $_uid = htmlspecialchars($uid);
    $fullname = htmlspecialchars($attributes["gecos"][0]);
    $mail = htmlspecialchars($attributes["mail"][0]);
    $gid = htmlspecialchars($group->gid);
    echo "<td>$fullname</td>";
    echo "<td>$_uid</td>";
    echo "<td><a href='mailto:$mail'>$mail</a></td>";
    echo "<td>";
    $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
    echo "
        <form
            action=''
            method='POST'
            onsubmit='
                return confirm(\"Are you sure you want to remove $_uid from this group?\");
            '
        >
        $CSRFTokenHiddenFormInput
        <input type='hidden' name='form_type' value='remUserChild'>
        <input type='hidden' name='uid' value='$_uid'>
        <input type='hidden' name='pi' value='$gid'>
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
    $name = htmlspecialchars($user->getFullName());
    $uid = htmlspecialchars($user->uid);
    $email = htmlspecialchars($user->getMail());
    $gid = htmlspecialchars($group->gid);
    echo "<td>$name</td>";
    echo "<td>$uid</td>";
    echo "<td><a href='mailto:$email'>$email</a></td>";
    echo "<td>";
    $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
    echo
        "<form action='' method='POST'
    onsubmit='return confirm(\"Are you sure you want to approve $uid ?\");'>
    $CSRFTokenHiddenFormInput
    <input type='hidden' name='form_type' value='reqChild'>
    <input type='hidden' name='uid' value='$uid'>
    <input type='hidden' name='pi' value='$gid'>
    <input type='submit' name='action' value='Approve'>
    <input type='submit' name='action' value='Deny'></form>";
    echo "</td>";
    echo "</tr>";
    $i++;
}
