<?php
require "../../resources/autoload.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modalErrors = array();
    $errors = array();

    if (isset($_POST["form_name"])) {
        switch ($_POST["form_name"]) {
            case "newPIform":
                // requesting a PI account, this is dealt by admins
                if ($user->isPI()) {
                    array_push($errors, "You are already a PI");
                }

                if ($sql->requestExists($user->getUID())) {
                    array_push($errors, "You've already requested this");
                }

                if (empty($errors)) {
                    //proceed
                    $sql->addRequest($user->getUID());

                    // Send approval email to admins
                    $mailer->send("new_pi_request", array("netid" => $user->getUID(), "firstname" => $user->getFirstname(), "lastname" => $user->getLastname(), "mail" => $user->getMail()));

                    $success = "A request for a PI account has been sent";
                }

                // 1. Check if PI is already a PI (DONE)
                // 2. Check if a PI request already exists (DONE)
                // 3. Add row to sql table (DONE)
                // 4. Send email to admins
                break;
            case "addPIform":
                // The new PI modal was submitted
                // existing PI request

                if (!isset($_POST["pi"]) || empty($_POST["pi"])) {
                    // PI was not set
                    array_push($modalErrors, "You have not chosen a PI");
                }

                if (!$sacctmgr->accountExists($_POST["pi"])) {
                    array_push($modalErrors, "This PI doesn't exist");
                }

                if ($sql->requestExists($user->getUID(), $_POST["pi"])) {
                    array_push($modalErrors, "You've already requested this");
                }

                // Add row to sql
                if (empty($modalErrors)) {
                    $sql->addRequest($user->getUID(), $_POST["pi"]);
                    $success = "A request for joining " . $_POST["pi"] . " has been sent";

                    $pi_owner = $user->getGroup($_POST["pi"])->getOwner();

                    // Send approval email to PI
                    $mailer->send("new_group_request", array("netid" => $user->getUID(), "firstname" => $user->getFirstname(), "lastname" => $user->getLastname(), "mail" => $user->getMail(), "to" => $pi_owner->getMail()));
                }

                // 1. Check if PI value was submitted (DONE)
                // 2. Check if submitted PI exists (DONE)
                // 3. Check if PI request exists already (DONE)
                // 4. Add row to sql table (DONE)
                // 5. Send email to existing PI
                break;
            case "removePIForm":
                // Remove PI form
                if (!$sacctmgr->accountExists($_POST["pi"])) {
                    array_push($modalErrors, "The requested account doesn't exist");
                }

                if (!$sacctmgr->userExists($user->getUID(), $_POST["pi"])) {
                    array_push($modalErrors, "You are not a member of this group");
                }

                if (empty($modalErrors)) {
                    $pi_user = $user->getGroup($_POST["pi"]);
                    $pi_user->removeSlurmAccount($user);

                    $mailer->send("left_user", array("netid" => $user->getUID(), "firstname" => $user->getFirstname(), "lastname" => $user->getLastname(), "mail" => $user->getMail(), "to" => $pi_owner->getMail()));
                }

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
$groups = $user->getGroups();

if (count($groups) == 0 && !$user->isPI()) {
    echo "<span>You are not assigned to any PI, you will not be able to log in via Jupyter or SSH until you are.</span>";
} else {
    echo "<table>";

    foreach ($groups as $group) {
        $owner = $group->getOwner();

        echo "<tr class='expandable'>";
        echo "<td><button class='btnExpand'>&#9654;</button>" . $owner->getFirstname() . " " . $owner->getLastname() . "</td>";
        echo "<td>" . $group->getPIUID() . "</td>";
        echo "<td><a href='mailto:" . $owner->getMail() . "'>" . $owner->getMail() . "</a></td>";
        echo "<td><form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to leave " . $group->getPIUID() . "?\");'><input type='hidden' name='form_name' value='removePIForm'><input type='hidden' name='pi' value='" . $group->getPIUID() . "'><div class='inline'><input type='submit' value='Leave Group'></div></form></td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
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

    var ajax_url = "<?php echo config::PREFIX; ?>/panel/ajax/get_group_members.php?pi_uid=";
</script>

<?php
include config::PATHS["templates"] . "/footer.php";
?>