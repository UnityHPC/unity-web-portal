<?php

namespace UnityWebPortal\lib;

require_once "../../resources/autoload.php";

$group = $USER->getPIGroup();

if (!$USER->isPI()) {
    $SITE->bad_request("User is not a PI");
}

include $LOC_HEADER;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_name = $SITE->array_get_or_bad_request("form_name", $_POST);
    switch ($form_name) {
        case "userReq":
            $action = $SITE->array_get_or_bad_request("action", $_POST);
            switch ($action) {
                case "Approve":
                    $uid = $SITE->array_get_or_bad_request("uid", $_POST);
                    $form_user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
                    try {
                        $group->approveUser($form_user);
                    } catch (UnitySQLRecordNotFoundException $e) {
                        $SITE->bad_request(
                            "no request for '" . $group->getPIUID() . "' from uid '$uid'"
                        );
                    }
                    break;
                case "Deny":
                    $uid = $SITE->array_get_or_bad_request("uid", $_POST);
                    $form_user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
                    try {
                        $group->denyUser($form_user);
                    } catch (UnitySQLRecordNotFoundException $e) {
                        $SITE->bad_request(
                            "no request for '" . $group->getPIUID() . "' from uid '$uid'"
                        );
                    }
                    break;
                default:
                    $SITE->bad_request("invalid action '$action'");
                    break;
            }
            break;
        case "remUser":
            $group->removeUser($form_user);
            break;
        case "disband":
            $group->removeGroup();
            $SITE->redirect($CONFIG["site"]["prefix"] . "/panel/account.php");
            break;
        default:
            $SITE->bad_request("invalid form_name '" . $_POST["form_name"] . "'");
            break;
    }
}

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

    foreach ($requests as $request) {
        echo "<tr>";
        echo "<td>" . $request[0]->getFirstname() . " " . $request[0]->getLastname() . "</td>";
        echo "<td>" . $request[0]->getUID() . "</td>";
        echo "<td><a href='mailto:" . $request[0]->getMail() . "'>" . $request[0]->getMail() . "</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request[1])) . "</td>";
        echo "<td>";
        echo
        "<form action='' method='POST'>
        <input type='hidden' name='form_name' value='userReq'>
        <input type='hidden' name='uid' value='" . $request[0]->getUID() . "'>
        <input type='submit' name='action' value='Approve' 
        onclick='return confirm(\"Are you sure you want to approve " . $request[0]->getUID() . "?\")'>
        <input type='submit' name='action' value='Deny' 
        onclick='return confirm(\"Are you sure you want to deny " . $request[0]->getUID() . "?\")'>
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
    if ($assoc->getUID() == $USER->getUID()) {
        continue;
    }

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

echo "<h5>Danger Zone</h5>";

echo
"<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to disband your PI group?\")'>
<input type='hidden' name='form_name' value='disband'>
<input type='submit' value='Disband PI Account'>
</form>
";
?>
</table>

<?php
include $LOC_FOOTER;
