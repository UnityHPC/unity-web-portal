<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnitySite;

if (!$USER->isAdmin()) {
    UnitySite::forbidden("not an admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    }

    switch ($_POST["form_name"]) {
        case "req":
            if ($_POST["action"] == "Approve") {
                // approve group
                $group = $form_user->getPIGroup();
                $group->approveGroup($OPERATOR);
            } elseif ($_POST["action"] == "Deny") {
                // deny group
                $group = $form_user->getPIGroup();
                $group->denyGroup($OPERATOR);
            }

            break;
        case "reqChild":
            $parent_group = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
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
            $parent = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            $parent->removeUser($form_user);

            break;
    }
}

include $LOC_HEADER;
?>

<h1>PI Management</h1>
<hr>

<!-- <input type="text" id="tableSearch" placeholder="Search..."> -->

<h5>Pending PI Requests</h5>
<table class="searchable">
    <tr class="key">
        <td>Name</td>
        <td>Unity ID</td>
        <td>Mail</td>
        <td>Requested On</td>
        <td>Actions</td>
    </tr>

    <?php
    $requests = $SQL->getRequests();

    foreach ($requests as $request) {
        $request_user = new UnityUser($request["uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);

        echo "<tr>";
        echo "<td>" . $request_user->getFirstname() . " " . $request_user->getLastname() . "</td>";
        echo "<td>" . $request_user->getUID() . "</td>";
        echo "<td><a href='mailto:" . $request_user->getMail() . "'>" . $request_user->getMail() . "</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request['timestamp'])) . "</td>";
        echo "<td>";
        echo
        "<form action='' method='POST'>
        <input type='hidden' name='form_name' value='req'>
        <input type='hidden' name='uid' value='" . $request_user->getUID() . "'>
        <input type='submit' name='action' value='Approve'
        onclick='return confirm(\"Are you sure you want to approve " . $request_user->getUID() . "?\");'>
        <input type='submit' name='action' value='Deny'
        onclick='return confirm(\"Are you sure you want to deny " . $request_user->getUID() . "?\");'>
        </form>";
        echo "</td>";
        echo "</tr>";
    }
    ?>

</table>

<h5>List of PIs</h5>

<table class="searchable longTable sortable filterable">
    <tr class="key">
        <input type="text" style="margin-right:5px;" placeholder="Filter by..." id="common-filter" class="filterSearch">
        <td id="name"><span class="filter">⫧ </span>Name</td>
        <td id="unityID"><span class="filter">⫧ </span>Unity ID</td>
        <td id="mail"><span class="filter">⫧ </span>Mail</td>
        <td>Actions</td>
    </tr>

<?php
    $accounts = $LDAP->getAllPIGroups($SQL, $MAILER, $REDIS, $WEBHOOK);

    usort($accounts, function ($a, $b) {
        return strcmp($a->getPIUID(), $b->getPIUID());
    });

    foreach ($accounts as $pi_group) {
        $pi_user = $pi_group->getOwner();

        echo "<tr class='expandable'>";
        echo "<td><button class='btnExpand'>&#9654;</button>" . $pi_user->getFirstname() .
        " " . $pi_user->getLastname() . "</td>";
        echo "<td>" . $pi_group->getPIUID() . "</td>";
        echo "<td><a href='mailto:" . $pi_user->getMail() . "'>" . $pi_user->getMail() . "</a></td>";
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
