<?php
require "../../resources/autoload.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modalErrors = array();
    $errors = array();

    if (isset($_POST["form_name"])) {
        $pi_account = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
        $pi_owner = $pi_account->getOwner();

        switch ($_POST["form_name"]) {
            case "addPIform":
                // The new PI modal was submitted
                // existing PI request

                if (!isset($_POST["pi"]) || empty($_POST["pi"])) {
                    // PI was not set
                    array_push($modalErrors, "You have not chosen a PI");
                }

                if (!$pi_account->exists()) {
                    // "\'"  instead of "'", otherwise it will close a single quote used to place the message
                    array_push($modalErrors, "This PI doesn\'t exist");
                }

                if ($pi_account->requestExists($USER)) {
                    array_push($modalErrors, "You've already requested this");
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

include LOC_HEADER;
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

if (count($groups) + count($req_filtered) == 0) {
    echo "<p>You do not have any PIs attached to your account. You need at least one to use the cluster. Click the button below to request.</p>";
}

if (count($req_filtered) > 0) {
    echo "<h3>Pending Requests</h3>";
    echo "<table>";
    foreach ($req_filtered as $request) {
        $requested_account = new UnityGroup($request["request_for"], $LDAP, $SQL, $MAILER);
        $requested_owner = $requested_account->getOwner();
        echo "<tr class='pending_request'>";
        echo "<td>" . $requested_owner->getFirstname() . " " . $requested_owner->getLastname() . "</td>";
        echo "<td>" . $requested_account->getPIUID() . "</td>";
        echo "<td><a href='mailto:" . $requested_owner->getMail() . "'>" . $requested_owner->getMail() . "</a></td>";
        echo "<td></td>";
        echo "</tr>";
    }
    echo "</table>";

    if (count($groups) > 0) {
        echo "<hr>";
    }
}


echo "<table>";

foreach ($groups as $group) {
    $owner = $group->getOwner();

    if ($USER->getUID() == $owner->getUID()) {
        continue;
    }

    echo "<tr class='expandable'>";
    echo "<td><button class='btnExpand'>&#9654;</button>" . $owner->getFirstname() . " " . $owner->getLastname() . "</td>";
    echo "<td>" . $group->getPIUID() . "</td>";
    echo "<td><a href='mailto:" . $owner->getMail() . "'>" . $owner->getMail() . "</a></td>";
    echo "<td><button class='leaveGroupBtn' data-group='" . $group->getPIUID() . "'>Leave Group</button><form action='' method='POST' id='leave-" . $group->getPIUID() . "'><input type='hidden' name='form_name' value='removePIForm'><input type='hidden' name='pi' value='" . $group->getPIUID() . "'></form></td>";
    echo "</tr>";
}

echo "</table>";
?>

<button type="button" class="plusBtn btnAddPI">&#43;</button>

<style>
    div.modalContent {
        max-width: 300px;
    }
</style>

<script>
    $("button.btnAddPI").click(function() {
        openModal("Add New PI", "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/new_pi.php");
    });

    <?php
    // This is here to re-open the modal if there are errors
    if (isset($modalErrors) && is_array($modalErrors) && count($modalErrors) > 0) {
        $errorHTML = "";
        foreach ($modalErrors as $error) {
            $errorHTML .= "<span>$error</span>";
        }

        echo "openModal('Add New PI', '" . $CONFIG["site"]["prefix"] . "/panel/modal/new_pi.php', '" . $errorHTML . "');";
    }
    ?>

    $("button.leaveGroupBtn").click(function() {
        var group = $(this).attr("data-group");
        confirmModal("Are you sure you want to leave " + group + "?", "#leave-" + group);
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
include LOC_FOOTER;
?>