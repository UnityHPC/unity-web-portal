<?php

namespace UnityWebPortal\lib;

require_once "../../resources/autoload.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["pi"])) {
        $pi_account = new UnityGroup(trim($_POST["pi"]), $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        if (!$pi_account->exists()) {
            $SITE->bad_request("pi '" . $_POST["pi"] . "' does not exist");
        }
    }
    $form_name = $SITE->array_get_or_bad_request("form_name", $_POST);
    switch ($form_name) {
        case "addPIform":
            $pi_groups = $USER->getPIGroups();
            $requests = $SQL->getRequestsByUser($USER->getUID());
            $max = $CONFIG["ldap"]["max_num_pi_groups_per_user"];
            if (count($pi_groups) + count($requests) >= $max) {
                $SITE->alert("You've already requested or joined the maximum number of PI groups");
                break;
            }
            try {
                $pi_account->newUserRequest($USER);
            } catch (UnityGroupUserRequestAlreadyMemberException $e) {
                $SITE->alert("You're already a member of this group.");
            } catch (UnityGroupDuplicateUserRequestException $e) {
                $SITE->alert("You've already requested to join this group.");
            } catch (UnityGroupUserRequestInvalidUserException $e) {
                $SITE->alert("You cannot request to join a group after requesting account deletion.");
            }
            break;
        case "removePIForm":
            // Remove PI form
            $pi_account->removeUser($USER);
            break;
        default:
            $SITE->bad_request("invalid form_name '" . $_POST["form_name"] . "'");
    }
}

include $LOC_HEADER;
?>

<h1>My Principal Investigators</h1>
<hr>

<?php
$pi_groups = $USER->getPIGroups();

$requests = $SQL->getRequestsByUser($USER->getUID());

$req_filtered = array();
foreach ($requests as $request) {
    // FIXME "admin" is UnitySQL::REQUEST_PI_PROMOTION
    // should implement UnitySQL::getPiPromotionRequests
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

    if (count($pi_groups) > 0) {
        echo "<hr>";
    }
}

echo "<h5>Current Groups</h5>";

if ($USER->isPI() && count($pi_groups) == 1) {
    echo "You are only a member of your own PI group. 
    Navigate to the <a href='" . $CONFIG["site"]["prefix"] . "/panel/pi.php'>my users</a> page to see your group.";
}

if (count($pi_groups) == 0) {
    echo "You are not a member of any groups. Request to join a PI using the button below, 
    or request your own PI account on the <a href='" . $CONFIG["site"]["prefix"] .
    "/panel/account.php'>account settings</a> page";
}

echo "<table>";

foreach ($pi_groups as $group) {
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
