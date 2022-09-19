<?php
require "../../resources/autoload.php";

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER);
    }

    switch ($_POST["form_name"]) {
        case "approveReq":
            $group = $form_user->getAccount();
            $group->approveGroup();

            break;
        case "denyReq":
            $group = $form_user->getAccount();
            $group->denyGroup();

            break;
        case "remUser":
            $remGroup = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
            $remGroup->removeGroup();

            break;
        case "approveReqChild":
            // approve request button clicked
            $parent = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
            $parent->approveUser($form_user);  // Add to group (ldap and slurm)

            break;
        case "denyReqChild":
            // deny request button clicked
            $parent = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
            $parent->denyUser($form_user);

            break;
        case "remUserChild":
            // remove user button clicked

            $parent = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
            $parent->removeUser($form_user);

            break;
    }
}

include LOC_HEADER;
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
    $requests = $SQL->getRequests();

    foreach ($requests as $request) {
        $request_user = new UnityUser($request["uid"], $LDAP, $SQL, $MAILER);

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
    $accounts = $LDAP->getAllPIGroups($SQL, $MAILER);

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

    var ajax_url = "<?php echo $CONFIG["site"]["prefix"]; ?>/admin/ajax/get_group_members.php?pi_uid=";
</script>

<?php
include LOC_FOOTER;
?>