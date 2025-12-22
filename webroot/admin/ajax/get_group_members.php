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
    $uid_escaped = htmlspecialchars($uid);
    $fullname = htmlspecialchars($attributes["gecos"][0]);
    $mail_link = "mailto:" . urlencode($attributes["mail"][0]);
    $mail_display = htmlspecialchars($attributes["mail"][0]);
    $gid_escaped = htmlspecialchars($group->gid);
    echo "<td>$fullname</td>";
    echo "<td>$uid_escaped</td>";
    echo "<td><a href='$mail_link'>$mail_display</a></td>";
    echo "<td>";
    $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
    echo "
        <form
            action=''
            method='POST'
            onsubmit='
                return confirm(\"Are you sure you want to remove $uid_escaped from this group?\");
            '
        >
        $CSRFTokenHiddenFormInput
        <input type='hidden' name='form_type' value='remUserChild'>
        <input type='hidden' name='uid' value='$uid_escaped'>
        <input type='hidden' name='pi' value='$gid_escaped'>
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
    $uid_escaped = htmlspecialchars($user->uid);
    $mail_link = "mailto:" . urlencode($user->getMail());
    $mail_display = htmlspecialchars($user->getMail());
    $gid_escaped = htmlspecialchars($group->gid);
    echo "<td>$name</td>";
    echo "<td>$uid_escaped</td>";
    echo "<td><a href='$mail_link'>$mail_display</a></td>";
    echo "<td>";
    $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
    echo
        "<form action='' method='POST'
    onsubmit='return confirm(\"Are you sure you want to approve $uid_escaped ?\");'>
    $CSRFTokenHiddenFormInput
    <input type='hidden' name='form_type' value='reqChild'>
    <input type='hidden' name='uid' value='$uid_escaped'>
    <input type='hidden' name='pi' value='$gid_escaped'>
    <input type='submit' name='action' value='Approve'>
    <input type='submit' name='action' value='Deny'></form>";
    echo "</td>";
    echo "</tr>";
    $i++;
}
