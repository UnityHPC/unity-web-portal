<?php
require "../../resources/autoload.php";

//$user = new unityUser("jriffin_umass_edu", $ldap, $sql, $sacctmgr); // ! DEBUG remove later
$group = $user->getAccount();

if (!$user->isPI()) {
    die("403");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["uid"])) {
        $form_user = $user->clone($_POST["uid"]);
    }

    switch ($_POST["form_name"]) {
        case "approveReq":
            // approve request button clicked

            $group->addUserToGroup($form_user);  // Add to group (ldap and slurm)

            try {
                $group->removeRequest($form_user->getUID());  // remove request from db

                // Send approval email to admins
                $mailer->send("join_pi", array("to" => $form_user->getMail(), "group" => $group->getPIUID()));
            } catch (Exception $e) {
                $group->removeUserFromGroup($form_user); // roll back
                echo $e->getMessage();  // ! DEBUG
            }

            // (1) Create slurm association [DONE]
            // (2) Remove SQL Row if (1) succeeded [DONE]
            // (3) Send email to requestor
            break;
        case "denyReq":
            // deny request button clicked

            $group = $user->getAccount();

            $group->removeRequest($form_user->getUID());  // remove request from db

            $mailer->send("deny_pi", array("to" => $form_user->getMail(), "group" => $group->getPIUID()));

            // (1) Remove SQL Row
            // (2) Send email to requestor
            break;
        case "remUser":
            // remove user button clicked

            $group = $user->getAccount();

            $group->removeUserFromGroup($form_user);

            $mailer->send("rem_pi", array("to" => $form_user->getMail(), "group" => $group->getPIUID()));

            // (1) Remove slurm association
            // (2) Send email to removed user
            break;
        case "disPI":
            // disband pi group

            $group = $user->getAccount();

            foreach ($group->getGroupMembers() as $assoc_user) {
                $group->removeUserFromGroup($assoc_user);

                $mailer->send("rem_pi", array("to" => $assoc_user->getMail(), "group" => $group->getPIUID()));
            }
            $group->removeGroup();

            header("Location: " . config::PREFIX . "/panel");  // redirect to panel

            // (1) Remove all children of group (send emails to all along the way)
            // (2) Remove account
            // (3) Send email to admins
            break;
    }
}

include config::PATHS["templates"] . "/header.php";
?>

<h1>PI Management</h1>

<form method='POST' action='' onsubmit='return confirm("Are you sure you want to disband your PI group?")'>
    <input type='hidden' name='form_name' value='disPI'>
    <input type='submit' class='btnNewPI' value='Disband PI Account'>
</form>

<table>
    <tr>
        <td>Name</td>
        <td>Unity ID</td>
        <td>Mail</td>
        <td>Actions</td>
    </tr>

    <?php
    $requests = $group->getRequests();

    foreach ($requests as $request) {
        echo "<tr>";
        echo "<td>" . $request->getFirstname() . " " . $request->getLastname() . "</td>";
        echo "<td>" . $request->getUID() . "</td>";
        echo "<td><a href='mailto:" . $request->getMail() . "'>" . $request->getMail() . "</a></td>";
        echo "<td>";
        echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to add " . $request->getUID() . "?\");'><input type='hidden' name='form_name' value='approveReq'><input type='hidden' name='uid' value='" . $request->getUID() . "'><input type='submit' value='Approve'></form>";
        echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to deny " . $request->getUID() . "?\");'><input type='hidden' name='form_name' value='denyReq'><input type='hidden' name='uid' value='" . $request->getUID() . "'><input type='submit' value='Deny'></form>";
        echo "</td>";
        echo "</tr>";
    }

    $assocs = $group->getGroupMembers();

    foreach ($assocs as $assoc) {
        echo "<tr>";
        echo "<td>" . $assoc->getFirstname() . " " . $assoc->getLastname() . "</td>";
        echo "<td>" . $assoc->getUID() . "</td>";
        echo "<td><a href='mailto:" . $assoc->getMail() . "'>" . $assoc->getMail() . "</a></td>";
        echo "<td>";
        echo "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to remove " . $assoc->getUID() . " from your group?\");'><input type='hidden' name='form_name' value='remUser'><input type='hidden' name='uid' value='" . $assoc->getUID() . "'><input type='submit' value='Remove'></form>";
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>

<?php
include config::PATHS["templates"] . "/footer.php";
?>