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
        case "req":
            if ($_POST["action"] == "Approve") {
                // approve group
                $group = $form_user->getPIGroup();
                $group->approveGroup();
            } elseif ($_POST["action"] == "Deny") {
                // deny group
                $group = $form_user->getPIGroup();
                $group->denyGroup();
            }

            break;
        case "remGroup":
            $remGroup = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
            $remGroup->removeGroup();

            break;
        case "reqChild":
            $parent_group = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
            if ($_POST["action"] == "Approve") {
                // initialize user if not initialized
                if (!$form_user->exists()) {
                    $form_user->init();
                }

                // approve request button clicked
                $parent_group->approveUser($form_user);  // Add to group (ldap and slurm)
            } elseif ($_POST["action"] == "Deny") {
                $parent_group->denyUser($form_user);
            }
            
            break;
        case "remUserChild":
            // remove user button clicked
            $parent = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER);
            $parent->removeUser($form_user);

            break;
    }
}

include $LOC_HEADER;
?>

<h1>PI Management</h1>
<hr>

<input type="text" id="tableSearch" placeholder="Search...">

<h5>Pending PI Requests</h5>
<table class="searchable">
    <tr class="key">
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
        echo 
        "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to perform action on " . $request_user->getUID() . "?\");'>
        <input type='hidden' name='form_name' value='req'>
        <input type='hidden' name='uid' value='" . $request_user->getUID() . "'>
        <input type='submit' name='action' value='Approve'>
        <input type='submit' name='action' value='Deny'>
        </form>";
        echo "</td>";
        echo "</tr>";
    }
?>

</table>

<h5>List of PIs</h5>

<table class="searchable">
    <tr class="key">
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
        echo 
        "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to remove " . $pi_group->getPIUID() . "? This will also remove associations for all users under this PI - the users themselves will not be deleted, nor will the PI user itself.\");'>
        <input type='hidden' name='form_name' value='remGroup'>
        <input type='hidden' name='pi' value='" . $pi_group->getPIUID() . "'>
        <input type='submit' value='Remove'>
        </form>";
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
include $LOC_FOOTER;
?>