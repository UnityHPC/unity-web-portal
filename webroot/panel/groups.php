<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modalErrors = array();
    $errors = array();

    if (isset($_POST["form_name"])) {
        if (isset($_POST["pi"])) {
            $pi_account = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            if (!$pi_account->exists()) {
                // "\'"  instead of "'", otherwise it will close a single quote used to place the message
                array_push($modalErrors, "This PI doesn\'t exist");
            }
        }

        switch ($_POST["form_name"]) {
            case "addPIform":
                // The new PI modal was submitted
                // existing PI request

                if ($pi_account->requestExists($USER)) {
                    array_push($modalErrors, "You\'ve already requested this");
                }

                if ($pi_account->userExists($USER)) {
                    array_push($modalErrors, "You\'re already in this PI group");
                }

                // Add row to sql
                if (empty($modalErrors)) {
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

<h1>My Groups</h1>
<hr>

<?php
$groups = $USER->getGroups(true);

$requests = $SQL->getJoinRequestsByUser($USER->getUID());

if (count($requests) > 0) {
    echo "<h5>Pending Requests</h5>";
    echo "<table>";
    foreach ($requests as $request) {
        $requested_account = new UnityGroup($request["group_name"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        echo "<tr class='pending_request'>";
        echo "<td> 
        <div class='type' style='border-radius: 5px; padding-left: 10px;
        color: white; padding-right: 10px; text-align: center; font-size: 12px;
        background-color: " . $requested_account->getGroupTypeColor() . ";'>"
        . $requested_account->getGroupTypeName() . "</div> </td>";
        echo "<td>" . $requested_account->getGroupUID() . "</td>";
        echo "<td>" . date("jS F, Y", strtotime($request['requested_on'])) . "</td>";
        echo "<td></td>";
        echo "</tr>";
    }
    echo "</table>";

    if (count($groups) > 0) {
        echo "<hr>";
    }
}

echo "<h5>Current Groups</h5>";

// if ($USER->isPI() && count($groups) == 1) {
//     echo "You are only a member of your own PI group.
//     Navigate to the <a href='" . $CONFIG["site"]["prefix"] . "/panel/pi.php'>my users</a> page to see your group.";
// }

if (count($groups) == 0) {
    echo "You are not a member of any groups. Request to join a group using the button below, 
    or request your own PI account on the <a href='" . $CONFIG["site"]["prefix"] .
    "/panel/account.php'>account settings</a> page";
}

echo "<table>";

foreach ($groups as $group) {
    echo "<tr class='expandable viewGroup'>";
    echo "<td> 
    <div class='type' style='border-radius: 5px; padding-left: 10px; 
    color: white; padding-right: 10px; text-align: center; font-size: 12px; 
    background-color: " . $group->getGroupTypeColor() . ";'>" . $group->getGroupTypeName() . "</div></td>";
    echo "<td>" . $group->getGroupUID() . "</td>";
    echo "<td> <button class='viewGroup'>View Group</button> </td>";
    echo "<input type='hidden' name='pi' value='" . $group->getGroupUID() . "'>";
    echo
    "<td>
    <form action='' method='POST' 
    onsubmit='return confirm(\"Are you sure you want to leave the group " . $group->getGroupUID() . "?\")'>
    <input type='hidden' name='form_name' value='removePIForm'>
    <input type='hidden' name='pi' value='" . $group->getGroupUID() . "'>
    <input type='submit' value='Leave Group' style='margin-top: 0px'>
    </form>
    </td>";
    echo "</tr>";
}

echo "</table>";
?>

<?php
if ($SQL->accDeletionRequestExists($USER->getUID())) {
    echo "<button type='button' class='plusBtn btnAddPI' disabled>&#43;</button>";
    echo "<label>You cannot join a group while you have requested account deletion.</label>";
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

    $("button.viewGroup").click(function() {
        $pi = $(this).parent().parent().find("input[name='pi']").val();
        window.location.href = "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/view_group.php?group=" + $pi;
    });

    $("tr.viewGroup").click(function() {
        $pi = $(this).find("input[name='pi']").val();
        window.location.href = "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/view_group.php?group=" + $pi;
    });

    <?php
    // This is here to re-open the modal if there are errors
    if (isset($modalErrors) && is_array($modalErrors) && count($modalErrors) > 0) {
        $errorHTML = "";
        foreach ($modalErrors as $error) {
            $errorHTML .= "<span>$error</span>";
        }

        echo "openModal('Add New PI', '" .
        $CONFIG["site"]["prefix"] . "/panel/modal/new_pi.php', '" . $errorHTML . "');";
    }
    ?>

    var ajax_url = "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/ajax/get_group_members.php?group_uid=";
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
