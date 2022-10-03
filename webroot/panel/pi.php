<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnityUser;

$group = $USER->getPIGroup();

if (!$USER->isPI()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER);
    }

    switch ($_POST["form_name"]) {
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

include $LOC_HEADER;
?>

<h1>My Users</h1>
<hr>

<?php
$requests = $group->getRequests();
$assocs = $group->getGroupMembers();

if (count($requests) + count($assocs) == 0) {
    echo "<p>You do not have any users attached to your PI account. 
    Ask your users to request to join your account on the <a href='" . $CONFIG["site"]["prefix"] .
    "/panel/groups.php'>My PIs</a> page.</p>";
}

if (count($requests) > 0) {
    echo "<h5>Pending Requests</h5>";
    echo "<table>";

    foreach ($requests as $request) {
        echo "<tr>";
        echo "<td>" . $request->getFirstname() . " " . $request->getLastname() . "</td>";
        echo "<td>" . $request->getUID() . "</td>";
        echo "<td><a href='mailto:" . $request->getMail() . "'>" . $request->getMail() . "</a></td>";
        echo "<td>";
        echo
        "<form action='' method='POST'>
        <input type='hidden' name='form_name' value='userReq'>
        <input type='hidden' name='uid' value='" . $request->getUID() . "'>
        <input type='submit' name='action' value='Approve' 
        onclick='return confirm(\"Are you sure you want to approve " . $request->getUID() . "?\")'>
        <input type='submit' name='action' value='Deny' 
        onclick='return confirm(\"Are you sure you want to deny " . $request->getUID() . "?\")'>
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
    echo "<tr>";
    echo "<td>";
    echo
    "<form action='' method='POST'>
    <input type='hidden' name='form_name' value='remUser'>
    <input type='hidden' name='uid' value='" . $assoc->getUID() . "'>
    <input type='submit' value='Remove' 
    onclick='return confirm(\"Are you sure you want to remove " . $assoc->getUID() . " from your PI group?\")'>
    </form>";
    echo "</td>";
    echo "<td>" . $assoc->getFirstname() . " " . $assoc->getLastname() . "</td>";
    echo "<td>" . $assoc->getUID() . "</td>";
    echo "<td><a href='mailto:" . $assoc->getMail() . "'>" . $assoc->getMail() . "</a></td>";
    echo "</tr>";
}

echo "</table>";
?>
</table>

<?php
include $LOC_FOOTER;
