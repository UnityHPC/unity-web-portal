<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnitySite;

if (!$USER->isAdmin()) {
    UnitySite::forbidden("not an admin");
}

if (!isset($_GET["pi_uid"])) {
    UnitySite::badRequest("PI UID not set");
}

$group = new UnityGroup($_GET["pi_uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
$members = $group->getGroupMembers();
$requests = $group->getRequests();

$i = 0;
$count = count($members) + count($requests);
foreach ($members as $member) {
    if ($member->getUID() == $group->getOwner()->getUID()) {
        continue;
    }

    if ($i >= $count - 1) {
        echo "<tr class='expanded $i last'>";
    } else {
        echo "<tr class='expanded $i'>";
    }

    echo "<td>" . $member->getFullname() . "</td>";
    echo "<td>" . $member->getUID() . "</td>";
    echo "<td><a href='mailto:" . $member->getMail() . "'>" . $member->getMail() . "</a></td>";
    echo "<td>";
    echo
    "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to remove " .
    $member->getUID() . " from this group?\");'>
    <input type='hidden' name='form_type' value='remUserChild'>
    <input type='hidden' name='uid' value='" . $member->getUID() . "'>
    <input type='hidden' name='pi' value='" . $group->getPIUID() . "'>
    <input type='submit' value='Remove'>
    </form>";
    echo "</td>";
    echo "</tr>";

    $i++;
}

foreach ($requests as $i => $request) {
    if ($i >= $count - 1) {
        echo "<tr class='expanded $i last'>";
    } else {
        echo "<tr class='expanded $i'>";
    }

    [$request, $timestamp] = $request;
    echo "<td>" . $request->getFirstname() . " " . $request->getLastname() . "</td>";
    echo "<td>" . $request->getUID() . "</td>";
    echo "<td><a href='mailto:" . $request->getMail() . "'>" . $request->getMail() . "</a></td>";
    echo "<td>";
    echo
    "<form action='' method='POST' 
    onsubmit='return confirm(\"Are you sure you want to approve " . $request->getUID() . "?\");'>
    <input type='hidden' name='form_type' value='reqChild'>
    <input type='hidden' name='uid' value='" . $request->getUID() . "'>
    <input type='hidden' name='pi' value='" . $group->getPIUID() . "'>
    <input type='submit' name='action' value='Approve'>
    <input type='submit' name='action' value='Deny'></form>";
    echo "</td>";
    echo "</tr>";

    $i++;
}
