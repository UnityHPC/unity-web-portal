<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\exceptions\EntryNotFoundException;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modalErrors = array();

    if (isset($_POST["form_type"])) {
        if (isset($_POST["pi"])) {
            $pi_groupname = $_POST["pi"];
            if (substr($pi_groupname, 0, 3) !== "pi_" && str_contains($pi_groupname, "@")) {
                try {
                    $pi_groupname = UnityGroup::ownerMail2GID($pi_groupname);
                } catch (EntryNotFoundException) {
                }
            }
            $pi_account = new UnityGroup($pi_groupname, $LDAP, $SQL, $MAILER, $WEBHOOK);
            if (!$pi_account->exists()) {
                array_push($modalErrors, "This PI doesn't exist");
            }
        }

        switch ($_POST["form_type"]) {
            case "addPIform":
                if (!isset($_POST["tos"]) || $_POST["tos"] != "agree") {
                    UnityHTTPD::badRequest("user did not agree to terms of service");
                }
                if ($pi_account->exists()) {
                    if ($pi_account->requestExists($USER)) {
                        array_push($modalErrors, "You've already requested this");
                    }
                    if ($pi_account->memberExists($USER)) {
                        array_push($modalErrors, "You're already in this PI group");
                    }
                }
                if (empty($modalErrors)) {
                    $pi_account->newUserRequest($USER);
                }
                break;
            case "removePIForm":
                $pi_account->removeUser($USER);
                break;
            case "cancelPIForm":
                $pi_account->cancelGroupJoinRequest($USER);
                break;
        }
    }
    $_SESSION['MODAL_ERRORS'] = $modalErrors;
} else {
    if (isset($_SESSION['MODAL_ERRORS'])) {
        $modalErrors = $_SESSION['MODAL_ERRORS'];
        $_SESSION['MODAL_ERRORS'] = array();  // Forget after shown
    }
}


require $LOC_HEADER;
?>

<h1>My Principal Investigators</h1>
<hr>

<?php
$PIGroupGIDs = $USER->getPIGroupGIDs();

$requests = $SQL->getRequestsByUser($USER->uid);

$req_filtered = array();
foreach ($requests as $request) {
    // FIXME "admin" -> UnitySQL::REQUEST_BECOME_PI
    if ($request["request_for"] != "admin") {  // put this in config later for gypsum
        array_push($req_filtered, $request);
    }
}

if (count($req_filtered) > 0) {
    echo "<h5>Pending Requests</h5>";
    echo "<table>";
    foreach ($req_filtered as $request) {
        $requested_account = new UnityGroup(
            $request["request_for"],
            $LDAP,
            $SQL,
            $MAILER,
            $WEBHOOK
        );
        $requested_owner = $requested_account->getOwner();
        $full_name = $requested_owner->getFirstname() . " " . $requested_owner->getLastname();
        $mail = $requested_owner->getMail();
        echo "<tr class='pending_request'>";
        echo "<td>$full_name</td>";
        echo "<td>" . $requested_account->gid . "</td>";
        echo "<td><a href='mailto:$mail'>$mail</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request['timestamp'])) . "</td>";
        echo "<td>";
        echo "<form action='' method='POST' id='cancelPI'>
            <input type='hidden' name='pi' value='{$requested_account->gid}'>
            <input type='hidden' name='form_type' value='cancelPIForm'>
            <input name='cancel' style='margin-top: 10px;' type='submit' value='Cancel Request'/>
            </form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    if (count($PIGroupGIDs) > 0) {
        echo "<hr>";
    }
}

echo "<h5>Current Groups</h5>";

if ($USER->isPI() && count($PIGroupGIDs) == 1) {
    echo "
        You are only a member of your own PI group.
        Navigate to the
        <a href='" . CONFIG["site"]["prefix"] . "/panel/pi.php'>my users</a>
        page to see your group.
    ";
}

if (count($PIGroupGIDs) == 0) {
    echo "You are not a member of any groups. Request to join a PI using the button below,
    or request your own PI account on the <a href='" . CONFIG["site"]["prefix"] .
        "/panel/account.php'>account settings</a> page";
}

echo "<table>";

foreach ($PIGroupGIDs as $gid) {
    $group = new UnityGroup($gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
    $owner = $group->getOwner();
    $full_name = $owner->getFirstname() . " " . $owner->getLastname();
    if ($USER->uid == $owner->uid) {
        continue;
    }

    echo "<tr class='expandable'>";
    echo "<td><button class='btnExpand'>&#9654;</button>$full_name</td>";
    echo "<td>" . $group->gid . "</td>";
    echo "<td><a href='mailto:" . $owner->getMail() . "'>" . $owner->getMail() . "</a></td>";
    echo
        "<td>
    <form action='' method='POST'
    onsubmit='return confirm(\"Are you sure you want to leave the PI group " . $group->gid . "?\")'>
    <input type='hidden' name='form_type' value='removePIForm'>
    <input type='hidden' name='pi' value='" . $group->gid . "'>
    <input type='submit' value='Leave Group'>
    </form>
    </td>";
    echo "</tr>";
}

echo "</table>";
?>

<?php
if ($SQL->accDeletionRequestExists($USER->uid)) {
    echo "<button type='button' class='plusBtn btnAddPI' disabled><span>&#43;</span></button>";
    echo "<label>You cannot join a PI while you have requested account deletion.</label>";
} else {
    echo "<button type='button' class='plusBtn btnAddPI'><span>&#43;</span></button>";
}
?>

<style>
    div.modalContent {
        max-width: 300px;
    }
</style>

<script>
    $("button.btnAddPI").click(function () {
        openModal("Add New PI", "<?php echo CONFIG["site"]["prefix"]; ?>/panel/modal/new_pi.php");
    });

    <?php
    // This is here to re-open the modal if there are errors
    if (isset($modalErrors) && is_array($modalErrors) && count($modalErrors) > 0) {
        $errorHTML = "";
        foreach ($modalErrors as $error) {
            $errorHTML .= "<span>" . htmlentities($error) . "</span>";
        }

        echo "openModal('Add New PI', '" . CONFIG["site"]["prefix"] . "/panel/modal/new_pi.php');";
    }
    ?>

    // tables.js uses ajax_url to populate expandable tables
    var ajax_url = "<?php echo CONFIG["site"]["prefix"]; ?>/panel/ajax/get_group_members.php?gid=";
</script>

<style>
    @media only screen and (max-width: 1000px) {
        table td:nth-child(2) {
            display: none;
        }
    }
</style>

<?php
require $LOC_FOOTER;
