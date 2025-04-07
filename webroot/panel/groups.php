<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modalErrors = array();
    $errors = array();

    if (isset($_POST["form_name"])) {
        $ok = true;
        if (isset($_POST["pi"])) {
            $pi_account = new UnityGroup(trim($_POST["pi"]), $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            if (!$pi_account->exists()) {
                $SITE->alert("This PI doesn't exist");
                $ok = false;
            }
        }

        switch ($_POST["form_name"]) {
            case "addPIform":
                // The new PI modal was submitted
                // existing PI request

                if ($pi_account->requestExists($USER)) {
                    $SITE->alert("You\'ve already requested this");
                    $ok = false;
                }

                if ($pi_account->userExists($USER)) {
                    $SITE->alert("You\'re already in this PI group");
                    $ok = false;
                }

                // Add row to sql
                if ($ok) {
                    $pi_account->newUserRequest($USER);
                }
                break;
            case "removePIForm":
                // Remove PI form
                $pi_account->removeUser($USER);
                break;
        }
    }
}

include $LOC_HEADER;
?>

<h1>My Principal Investigators</h1>
<hr>

<?php
$groups = $USER->getGroups();

$requests = $SQL->getRequestsByUser($USER->getUID());

$req_filtered = array();
foreach ($requests as $request) {
    if ($request["request_for"] != "admin") {  // put this in config later for gypsum
        array_push($req_filtered, $request);
    }
}

if (count($req_filtered) > 0) {
    echo "<h5>Pending Requests</h5>";
    echo "<table>";
    foreach ($req_filtered as $request) {
        $requested_account = new UnityGroup($request["request_for"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        $requested_owner = $requested_account->getOwner();
        echo "<tr class='pending_request'>";
        echo "<td>" . $requested_owner->getFirstname() . " " . $requested_owner->getLastname() . "</td>";
        echo "<td>" . $requested_account->getPIUID() . "</td>";
        echo "<td><a href='mailto:" . $requested_owner->getMail() . "'>" . $requested_owner->getMail() . "</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request['timestamp'])) . "</td>";
        echo "<td></td>";
        echo "</tr>";
    }
    echo "</table>";

    if (count($groups) > 0) {
        echo "<hr>";
    }
}

echo "<h5>Current Groups</h5>";

if ($USER->isPI() && count($groups) == 1) {
    echo "You are only a member of your own PI group. 
    Navigate to the <a href='" . $CONFIG["site"]["prefix"] . "/panel/pi.php'>my users</a> page to see your group.";
}

if (count($groups) == 0) {
    echo "You are not a member of any groups. Request to join a PI using the button below, 
    or request your own PI account on the <a href='" . $CONFIG["site"]["prefix"] .
    "/panel/account.php'>account settings</a> page";
}

echo "<table>";

foreach ($groups as $group) {
    $owner = $group->getOwner();

    if ($USER->getUID() == $owner->getUID()) {
        continue;
    }

    echo "<tr class='expandable'>";
    echo
    "<td>
    <button class='btnExpand'>&#9654;</button>" . $owner->getFirstname() . " " . $owner->getLastname() . "</td>";
    echo "<td>" . $group->getPIUID() . "</td>";
    echo "<td><a href='mailto:" . $owner->getMail() . "'>" . $owner->getMail() . "</a></td>";
    echo
    "<td>
    <form action='' method='POST' 
    onsubmit='return confirm(\"Are you sure you want to leave the PI group " . $group->getPIUID() . "?\")'>
    <input type='hidden' name='form_name' value='removePIForm'>
    <input type='hidden' name='pi' value='" . $group->getPIUID() . "'>
    <input type='submit' value='Leave Group'>
    </form>
    </td>";
    echo "</tr>";
}

echo "</table>";
?>

<?php
if ($SQL->accDeletionRequestExists($USER->getUID())) {
    echo "<button type='button' class='plusBtn btnAddPI' disabled>&#43;</button>";
    echo "<label>You cannot join a PI while you have requested account deletion.</label>";
} else {
    echo "<button type='button' class='plusBtn btnAddPI'>&#43;</button>";
}
?>

<style>
    div.modalContent {
        max-width: 300px;
    }
</style>

<script>
    $("button.btnAddPI").click(function() {
        openModal("Add New PI", "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/new_pi.php");
    });

    var ajax_url = "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/ajax/get_group_members.php?pi_uid=";
</script>

<style>
    @media only screen and (max-width: 1000px) {
        table td:nth-child(2) {
            display: none;
        }
    }
</style>

<?php
include $LOC_FOOTER;
