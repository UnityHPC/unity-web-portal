<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnitySite;

$group = $USER->getPIGroup();

if (!$USER->isPI()) {
    UnitySite::forbidden("not a PI");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    }

    switch ($_POST["form_type"]) {
        case "userReq":
            if ($_POST["action"] == "Approve") {
                $group->approveUser($form_user);
            } elseif ($_POST["action"] == "Deny") {
                $group->denyUser($form_user);
            }

            break;
        case "remUser":
            // remove user button clicked
            $group->removeUser($form_user);

            break;
    }
}

require $LOC_HEADER;
?>

<h1>My Users</h1>
<hr>

<?php
$requests = $group->getRequests();
$assocs = $group->getGroupMembers();

if (count($requests) + count($assocs) == 1) {
    echo "<p>You do not have any users attached to your PI account.
    Ask your users to request to join your account on the <a href='" . $CONFIG["site"]["prefix"] .
        "/panel/groups.php'>My PIs</a> page.</p>";
}

if (count($requests) > 0) {
    echo "<h5>Pending Requests</h5>";
    echo "<table>";

    foreach ($requests as [$user, $timestamp, $firstname, $lastname, $email, $org]) {
        $uid = $user->uid;
        $date = date("jS F, Y", strtotime($timestamp));
        echo "<tr>";
        echo "<td>" . $firstname . " " . $lastname . "</td>";
        echo "<td>" . $uid . "</td>";
        echo "<td><a href='mailto:" . $email . "'>" . $email . "</a></td>";
        echo "<td>" . $date . "</td>";
        echo "<td>";
        echo
            "<form action='' method='POST'>
        <input type='hidden' name='form_type' value='userReq'>
        <input type='hidden' name='uid' value='" . $uid . "'>
        <input type='submit' name='action' value='Approve'
        onclick='return confirm(\"Are you sure you want to approve " . $uid . "?\")'>
        <input type='submit' name='action' value='Deny'
        onclick='return confirm(\"Are you sure you want to deny " . $uid . "?\")'>
        </form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    if (count($assocs) > 0) {
        echo "<hr>";
    }
}

echo "<h5>Users in Group</h5>";

echo "<table>";

foreach ($assocs as $assoc) {
    if ($assoc->uid == $USER->uid) {
        continue;
    }

    echo "<tr>";
    echo "<td>";
    echo
        "<form action='' method='POST'>
    <input type='hidden' name='form_type' value='remUser'>
    <input type='hidden' name='uid' value='" . $assoc->uid . "'>
    <input type='submit' value='Remove'
    onclick='return confirm(\"Are you sure you want to remove " . $assoc->uid . " from your PI group?\")'>
    </form>";
    echo "</td>";
    echo "<td>" . $assoc->getFirstname() . " " . $assoc->getLastname() . "</td>";
    echo "<td>" . $assoc->uid . "</td>";
    echo "<td><a href='mailto:" . $assoc->getMail() . "'>" . $assoc->getMail() . "</a></td>";
    echo "</tr>";
}

echo "</table>";
?>

<?php
require $LOC_FOOTER;
