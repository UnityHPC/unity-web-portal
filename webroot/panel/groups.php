<?php
require "../../resources/autoload.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modalErrors = array();
    $errors = array();

    if (isset($_POST["form_name"])) {
        $pi_account = new unityAccount($_POST["pi"], $SERVICE);
        $pi_owner = $pi_account->getOwner();

        switch ($_POST["form_name"]) {
            case "addPIform":
                // The new PI modal was submitted
                // existing PI request

                if (!isset($_POST["pi"]) || empty($_POST["pi"])) {
                    // PI was not set
                    array_push($modalErrors, "You have not chosen a PI");
                }

                if (!$SERVICE->sacctmgr()->accountExists($_POST["pi"])) {
                    array_push($modalErrors, "This PI doesn't exist");
                }

                if ($SERVICE->sql()->requestExists($USER->getUID(), $_POST["pi"])) {
                    array_push($modalErrors, "You've already requested this");
                }

                // Add row to sql
                if (empty($modalErrors)) {
                    $SERVICE->sql()->addRequest($USER->getUID(), $_POST["pi"]);
                    $message = "A request for joining " . $_POST["pi"] . " has been sent";

                    // Send approval email to PI
                    $SERVICE->mail()->send("new_group_request", array("netid" => $USER->getUID(), "firstname" => $USER->getFirstname(), "lastname" => $USER->getLastname(), "mail" => $USER->getMail(), "to" => $pi_owner->getMail()));
                }

                // 1. Check if PI value was submitted (DONE)
                // 2. Check if submitted PI exists (DONE)
                // 3. Check if PI request exists already (DONE)
                // 4. Add row to sql table (DONE)
                // 5. Send email to existing PI
                break;
            case "removePIForm":
                // Remove PI form
                if (!$SERVICE->sacctmgr()->accountExists($_POST["pi"])) {
                    break;
                }

                if (!$SERVICE->sacctmgr()->userExists($USER->getUID(), $_POST["pi"])) {
                    break;
                }

                $pi_user = new unityAccount($_POST["pi"], $SERVICE);
                $pi_user->removeUserFromGroup($USER);

                $SERVICE->mail()->send("left_user", array("netid" => $USER->getUID(), "firstname" => $USER->getFirstname(), "lastname" => $USER->getLastname(), "mail" => $USER->getMail(), "to" => $pi_owner->getMail()));

                // 1. check if pi group exists (DONE)
                // 1. Check the selected PI actually belongs to this user (DONE)
                // 3. Remove slurm associations (DONE)
                break;
        }
    }
}

include config::PATHS["templates"] . "/header.php";
?>

<h1><?php echo unity_locale::GROUP_HEADER_MAIN; ?></h1>

<div class="pageMessages">
    <?php
    if (isset($errors) && is_array($errors) && count($errors) > 0) {
        foreach ($errors as $error) {
            echo "<span class='message-failure'>" . unity_locale::LABEL_ERROR . " $error</span>";
        }
    }

    if (isset($success)) {
        echo "<span class='message-success'>" . unity_locale::LABEL_SUCCESS . " $success</span>";
    }
    ?>
</div>

<?php
$groups = $USER->getGroups();

$requests = $SERVICE->sql()->getRequestsByUser($USER->getUID());
$req_filtered = array();
foreach ($requests as $request) {
    if ($request["request_for"] != "admin") {  // put this in config later for gypsum
        array_push($req_filtered, $request);
    }
}
if (count($req_filtered) > 0) {
    echo "<h3>Pending Requests</h3>";
    echo "<table>";
    foreach ($req_filtered as $request) {
        $requested_account = new unityAccount($request["request_for"], $SERVICE);
        $requested_owner = $requested_account->getOwner();
        echo "<tr class='pending_request'>";
        echo "<td>" . $requested_owner->getFirstname() . " " . $requested_owner->getLastname() . "</td>";
        echo "<td>" . $requested_account->getPIUID() . "</td>";
        echo "<td><a href='mailto:" . $requested_owner->getMail() . "'>" . $requested_owner->getMail() . "</a></td>";
        echo "<td></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<hr>";
}


echo "<table>";

foreach ($groups as $group) {
    $owner = $group->getOwner();

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
    button.plusBtn {
        max-width: 1200px;
    }

    div.modalContent {
        max-width: 300px;
    }
</style>

<script>
    $("button.btnAddPI").click(function() {
        openModal("Add New PI", "<?php echo config::PREFIX; ?>/panel/modal/new_pi.php");
    });

    <?php
    // This is here to re-open the modal if there are errors
    if (isset($modalErrors) && is_array($modalErrors) && count($modalErrors) > 0) {
        $errorHTML = "";
        foreach ($modalErrors as $error) {
            $errorHTML .= "<span>$error</span>";
        }

        echo "openModal('Add New PI', '" . config::PREFIX . "/panel/modal/new_pi.php', '" . $errorHTML . "');";
    }
    ?>

    $("button.leaveGroupBtn").click(function() {
        var group = $(this).attr("data-group");
        confirmModal("Are you sure you want to leave " + group + "?", "#leave-" + group);
    });

    var ajax_url = "<?php echo config::PREFIX; ?>/panel/ajax/get_group_members.php?pi_uid=";
</script>

<?php
include config::PATHS["templates"] . "/footer.php";
?>