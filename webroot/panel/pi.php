<?php
require "../../resources/autoload.php";

//$USER = new UnityUser("jgriffin_umass_edu", $LDAP, $SQL, $MAILER); // ! DEBUG remove later
$group = $USER->getAccount();

if (!$USER->isPI()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER);
    }

    switch ($_POST["form_name"]) {
        case "approveReq":
            // approve request button clicked

            $group->approveUser($form_user);  // Add to group, this also removes the request and send an email to the user
            break;
        case "denyReq":
            // deny request button clicked

            $group->denyUser($form_user);
            break;
        case "remUser":
            // remove user button clicked

            $group->removeUser($form_user);
            break;
    }
}

include LOC_HEADER;
?>

<h1>My Users</h1>
<hr>

<?php
$requests = $group->getRequests();
$assocs = $group->getGroupMembers();

if (count($requests) + count($assocs) == 0) {
    echo "<p>You do not have any users attached to your PI account. Ask your users to request to join your account on the <a href='" . $CONFIG["site"]["prefix"] . "/panel/groups.php'>My PIs</a> page.</p>";
} else {
    echo "<p>The following users are attached to your PI group and are authorized to use Unity</p>";
}

if (count($requests) > 0) {
    echo "<h3>Pending Requests</h3>";
    echo "<table>";

    foreach ($requests as $request) {
        echo "<tr>";
        echo "<td>" . $request->getFirstname() . " " . $request->getLastname() . "</td>";
        echo "<td>" . $request->getUID() . "</td>";
        echo "<td><a href='mailto:" . $request->getMail() . "'>" . $request->getMail() . "</a></td>";
        echo "<td>";
        echo "<button class='btnApprove' data-uid='" . $request->getUID() . "'>Approve</button><form action='' method='POST' id='approve-" . $request->getUID() . "'><input type='hidden' name='form_name' value='approveReq'><input type='hidden' name='uid' value='" . $request->getUID() . "'></form>";
        echo "<button class='btnDeny' data-uid='" . $request->getUID() . "'>Deny</button><form action='' method='POST' id='deny-" . $request->getUID() . "'><input type='hidden' name='form_name' value='denyReq'><input type='hidden' name='uid' value='" . $request->getUID() . "'></form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    if (count($assocs) > 0) {
        echo "<hr>";
    }
}

echo "<table>";

foreach ($assocs as $assoc) {
    echo "<tr>";
    echo "<td>" . $assoc->getFirstname() . " " . $assoc->getLastname() . "</td>";
    echo "<td>" . $assoc->getUID() . "</td>";
    echo "<td><a href='mailto:" . $assoc->getMail() . "'>" . $assoc->getMail() . "</a></td>";
    echo "<td>";
    echo "<button class='btnRemove' data-uid='" . $assoc->getUID() . "'>Remove</button><form action='' method='POST' id='remove-" . $assoc->getUID() . "'><input type='hidden' name='form_name' value='remUser'><input type='hidden' name='uid' value='" . $assoc->getUID() . "'></form>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
?>
</table>

<script>
    $("button.btnApprove").click(function() {
        var uid = $(this).attr("data-uid");
        confirmModal("Are you sure you want to approve " + uid + "? They will be allowed to use the cluster with your endorsement.", "#approve-" + uid);
    });

    $("button.btnDeny").click(function() {
        var uid = $(this).attr("data-uid");
        confirmModal("Are you sure you want to deny " + uid + "?", "#deny-" + uid);
    });

    $("button.btnRemove").click(function() {
        var uid = $(this).attr("data-uid");
        confirmModal("Are you sure you want to remove " + uid + " from your group?", "#remove-" + uid);
    });
</script>

<style>
button.btnDeny {
    margin-right: 10px;
}
</style>

<?php
include LOC_FOOTER;
?>