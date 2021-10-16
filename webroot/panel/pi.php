<?php
require "../../resources/autoload.php";

$USER = new unityUser("jgriffin_umass_edu", $SERVICE); // ! DEBUG remove later
$group = $USER->getAccount();

if (!$USER->isPI()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["uid"])) {
        $form_user = new unityUser($_POST["uid"], $SERVICE);
    }

    switch ($_POST["form_name"]) {
        case "approveReq":
            // approve request button clicked

            $group->addUserToGroup($form_user);  // Add to group (ldap and slurm)

            $group->removeRequest($form_user->getUID());  // remove request from db

            // Send approval email to admins
            $SERVICE->mail()->send("join_pi", array("to" => $form_user->getMail(), "group" => $group->getPIUID()));

            // (1) Create slurm association [DONE]
            // (2) Remove SQL Row if (1) succeeded [DONE]
            // (3) Send email to requestor
            break;
        case "denyReq":
            // deny request button clicked

            $group = $user->getAccount();

            $group->removeRequest($form_user->getUID());  // remove request from db

            $SERVICE->mail()->send("deny_pi", array("to" => $form_user->getMail(), "group" => $group->getPIUID()));

            // (1) Remove SQL Row
            // (2) Send email to requestor
            break;
        case "remUser":
            // remove user button clicked

            $group = $USER->getAccount();

            $group->removeUserFromGroup($form_user);

            $SERVICE->mail()->send("rem_pi", array("to" => $form_user->getMail(), "group" => $group->getPIUID()));

            // (1) Remove slurm association
            // (2) Send email to removed user
            break;
    }
}

include config::PATHS["templates"] . "/header.php";
?>

<h1>My Users</h1>

<p>The following users are attached to your PI group and are authorized to use Unity</p>

    <?php
    $requests = $group->getRequests();

    if (count($requests) > 0) {
        echo "<h3>Pending Requests</h3>";
        echo "<table>";
    
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
        echo "</table>";
        echo "<hr>";
    }

    echo "<table>";
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

    echo "</table>";
    ?>
</table>

<?php
include config::PATHS["templates"] . "/footer.php";
?>