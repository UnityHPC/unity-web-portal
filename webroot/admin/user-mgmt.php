<?php
require "../../resources/autoload.php";

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["uid"])) {
        $form_user = new unityUser($_POST["uid"], $SERVICE);
    }

    switch ($_POST["form_name"]) {
        case "approveReq":
            $group = $form_user->getAccount();
            if (!$form_user->isPI()) {
                $group->createGroup();
            }

            $SERVICE->sql()->removeRequest($form_user->getUID());

            $SERVICE->mail()->send("admin_approve_pi", array("to" => $form_user->getMail()));

            // (1) Create Slurm Account
            // (2) Create LDAP Group
            // (3) Remove SQL Row for Request
            // (4) Send email to new PI
            break;
        case "denyReq":
            $SERVICE->sql()->removeRequest($form_user->getUID());

            $SERVICE->mail()->send("admin_deny_pi", array("to" => $form_user->getMail()));

            // (1) Remove SQL Row request
            // (2) Send email to requestor
            break;
        case "remUser":
            $remGroup = new unityAccount($_POST["pi"], $SERVICE);

            if ($remGroup->exists()) {
                foreach ($remGroup->getGroupMembers() as $member) {
                    $remGroup->removeUserFromGroup($member);
                    
                    $SERVICE->mail()->send("rem_pi", array("to" => $member->getMail(), "group" => $remGroup->getPIUID()));
                }
            }
            $remGroup->removeGroup();

            $SERVICE->mail()->send("admin_disband_pi", array("to" => $remGroup->getOwner()->getMail()));

            // (same as disband PI from pi.php), except also send email to PI
            break;
        case "approveReqChild":
            // approve request button clicked
            $parent = new unityAccount($_POST["pi"], $SERVICE);

            $parent->addUserToGroup($form_user);  // Add to group (ldap and slurm)

            try {
                $parent->removeRequest($form_user->getUID());  // remove request from db
            } catch (Exception $e) {
                $parent->removeUserFromGroup($form_user); // roll back
                echo $e->getMessage();  // ! DEBUG
            }

            $SERVICE->mail()->send("join_pi", array("to" => $form_user->getMail(), "group" => $parent->getPIUID()));

            // (1) Create slurm association [DONE]
            // (2) Remove SQL Row if (1) succeeded [DONE]
            // (3) Send email to requestor
            break;
        case "denyReqChild":
            // deny request button clicked

            $parent = new unityAccount($_POST["pi"], $SERVICE);

            $parent->removeRequest($form_user->getUID());  // remove request from db

            $SERVICE->mail()->send("deny_pi", array("to" => $form_user->getMail(), "group" => $parent->getPIUID()));

            // (1) Remove SQL Row
            // (2) Send email to requestor
            break;
        case "remUserChild":
            // remove user button clicked

            $parent = new unityAccount($_POST["pi"], $SERVICE);

            $parent->removeUserFromGroup($form_user);

            $SERVICE->mail()->send("rem_pi", array("to" => $form_user->getMail(), "group" => $parent->getPIUID()));
            $SERVICE->mail()->send("left_user", array("netid" => $form_user->getUID(), "firstname" => $form_user->getFirstname(), "lastname" => $form_user->getLastname(), "mail" => $form_user->getMail(), "to" => $parent->getOwner()->getMail()));

            // (1) Remove slurm association
            // (2) Send email to removed user
            break;
    }
}

include config::PATHS["templates"] . "/header.php";
?>

<h1>User Management</h1>
<hr>

<h3>Pending PI Requests</h3>
<table>
    <tr>
        <td>Name</td>
        <td>Unity ID</td>
        <td>Mail</td>
        <td>Actions</td>
    </tr>

    <?php
    $requests = $SERVICE->sql()->getRequests();

    foreach ($requests as $request) {
        $request_user = new unityUser($request["uid"], $SERVICE);

        echo "<tr>";
        echo "<td>" . $request_user->getFirstname() . " " . $request_user->getLastname() . "</td>";
        echo "<td>" . $request_user->getUID() . "</td>";
        echo "<td><a href='mailto:" . $request_user->getMail() . "'>" . $request_user->getMail() . "</a></td>";
        echo "<td>";
        echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to approve " . $request_user->getUID() . "?\");'><input type='hidden' name='form_name' value='approveReq'><input type='hidden' name='uid' value='" . $request_user->getUID() . "'><input type='submit' value='Approve'></form>";
        echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to deny " . $request_user->getUID() . "?\");'><input type='hidden' name='form_name' value='denyReq'><input type='hidden' name='uid' value='" . $request_user->getUID() . "'><input type='submit' value='Deny'></form>";
        echo "</td>";
        echo "</tr>";
    }
?>

</table>
<hr>

<h3>List of PIs</h3>
<table>
    <tr>
        <td>Name</td>
        <td>Unity ID</td>
        <td>Mail</td>
        <td>Actions</td>
    </tr>

<?php
    $accounts = $SERVICE->ldap()->getAllPIGroups($SERVICE);

    foreach ($accounts as $pi_group) {
        $pi_user = $pi_group->getOwner();

        echo "<tr class='expandable'>";
        echo "<td><button class='btnExpand'>&#9654;</button>" . $pi_user->getFirstname() . " " . $pi_user->getLastname() . "</td>";
        echo "<td>" . $pi_group->getPIUID() . "</td>";
        echo "<td><a href='mailto:" . $pi_user->getMail() . "'>" . $pi_user->getMail() . "</a></td>";
        echo "<td>";
        echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to remove " . $pi_group->getPIUID() . "? This will also remove associations for all users under this PI - the users themselves will not be deleted, nor will the PI user itself.\");'><input type='hidden' name='form_name' value='remUser'><input type='hidden' name='pi' value='" . $pi_group->getPIUID() . "'><input type='submit' value='Remove'></form>";
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>

<script>
    $("table tr.tr-pichild").hide(); // Hide the children first (and then the women)

    $("table tr").click(function() {
        if (!$(this).hasClass("tr-pichild")) {
            var current = $(this).next();
            while (current.hasClass("tr-pichild")) {
                if (current.is(":visible")) {
                    current.hide();
                } else {
                    current.show();
                }
                current = current.next();
            }
        }
    });

    var ajax_url = "<?php echo config::PREFIX; ?>/admin/ajax/get_group_members.php?pi_uid=";
</script>

<?php
include config::PATHS["templates"] . "/footer.php";
?>