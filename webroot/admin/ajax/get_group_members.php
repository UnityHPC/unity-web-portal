<?php

require "../../../resources/autoload.php";

if (!isset($_GET["pi_uid"])) {
    die("PI UID not set");
}

$group = new unityAccount($_GET["pi_uid"], $SERVICE);
$members = $group->getGroupMembers();
$requests = $group->getRequests();

$key = 0;
$count = count($members) + count($requests);
foreach ($members as $member) {
    if ($key >= $count - 1) {
        echo "<tr class='expanded $key last'>";
    } else {
        echo "<tr class='expanded $key'>";
    }

    echo "<td>" . $member->getFullname() . "</td>";
    echo "<td>" . $member->getUID() . "</td>";
    echo "<td><a href='mailto:" . $member->getMail() . "'>" . $member->getMail() . "</a></td>";
    echo "<td>";
    echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to remove " . $member->getUID() . " from this group?\");'><input type='hidden' name='form_name' value='remUserChild'><input type='hidden' name='uid' value='" . $member->getUID() . "'><input type='hidden' name='pi' value='" . $group->getPIUID() . "'><input type='submit' value='Remove'></form>";
    echo "</td>";
    echo "</tr>";

    $key++;
}

foreach ($requests as $key=>$request) {
    if ($key >= $count - 1) {
        echo "<tr class='expanded $key last'>";
    } else {
        echo "<tr class='expanded $key'>";
    }
    
    echo "<td>" . $request->getFirstname() . " " . $request->getLastname() . "</td>";
    echo "<td>" . $request->getUID() . "</td>";
    echo "<td><a href='mailto:" . $request->getMail() . "'>" . $request->getMail() . "</a></td>";
    echo "<td>";
    echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to approve " . $request->getUID() . "?\");'><input type='hidden' name='form_name' value='approveReqChild'><input type='hidden' name='uid' value='" . $request->getUID() . "'><input type='hidden' name='pi' value='" . $group->getPIUID() . "'><input type='submit' value='Approve'></form>";
    echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to deny " . $request->getUID() . "?\");'><input type='hidden' name='form_name' value='denyReqChild'><input type='hidden' name='uid' value='" . $request->getUID() . "'><input type='hidden' name='pi' value='" . $group->getPIUID() . "'><input type='submit' value='Deny'></form>";
    echo "</td>";
    echo "</tr>";

    $key++;
}