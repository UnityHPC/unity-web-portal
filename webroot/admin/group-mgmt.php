<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    }

    switch ($_POST["form_name"]) {
        case "req":
            if ($_POST["action"] == "Approve") {
                // approve group
                $group_type = $_POST["group_type"];
                $group_name = $_POST["group_name"];
                $group_uid = $group_type . "_" . $group_name;
                $group = new UnityGroup($group_uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
                $group->approveGroup($_POST["uid"], $OPERATOR);
            } elseif ($_POST["action"] == "Deny") {
                // deny group
                $group_type = $_POST["group_type"];
                $group_name = $_POST["group_name"];
                $group_uid = $group_type . "_" . $group_name;
                $group = new UnityGroup($group_uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
                $group->denyGroup($_POST["uid"], $OPERATOR);
            }

            break;
        case "remGroup":
            $remGroup = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            $remGroup->removeGroup();

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

<h1>Group Management</h1>
<hr>

<input type="text" id="tableSearch" placeholder="Search...">

<h5>Pending Group Requests</h5>
<table class="searchable">
    <tr class="key">
        <td>Group Name</td>
        <td>Group Type</td>
        <td>Requestor</td>
        <td>Requestor UID</td>
        <td>Start/End Dates</td>
        <td>Mail</td>
        <td>Requested On</td>
        <td>Actions</td>
    </tr>

    <?php
    $requests = $SQL->getGroupRequests();
    $types = $USER->getRequestableGroupTypes();

    foreach ($requests as $request) {
        $request_user = new UnityUser($request["requestor"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);

        echo "<tr>";
        echo "<td>" . $request['group_name'] . "</td>";
        echo "<td> <div class='type' style='border-radius: 5px; padding-left: 10px; padding-right: 10px; 
        text-align: center; font-size: 12px; color: white; background-color: " . '#800000' . ";'>" .
         $USER->getTypeNameFromSlug($types, $request['group_type']) . "</div></td>";
        echo "<td>" . $request_user->getFirstname() . " " . $request_user->getLastname() . "</td>";
        echo "<td>" . $request_user->getUID() . "</td>";
        if ($request['start_date'] == null || $request['end_date'] == null) {
            echo "<td></td>";
        } else {
            echo "<td>" . date("jS F, Y", strtotime($request['start_date'])) . " - " 
            . date("jS F, Y", strtotime($request['end_date'])) . "</td>";
        }
        echo "<td><a href='mailto:" . $request_user->getMail() . "'>" . $request_user->getMail() . "</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request['requested_on'])) . "</td>";
        echo "<td>";
        echo
        "<form action='' method='POST'>
        <input type='hidden' name='form_name' value='req'>
        <input type='hidden' name='uid' value='" . $request_user->getUID() . "'>
        <input type='hidden' name='group_name' value='" . $request['group_name'] . "'>
        <input type='hidden' name='group_type' value='" . $request['group_type'] . "'>
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

<h5>List of Groups</h5>

<table class="searchable longTable">
    <tr class="key">
        <td>Group Type</td>
        <td>Group Name</td>
        <td>Actions</td>
    </tr>

<?php
    $accounts = $LDAP->getAllUnityGroups($SQL, $MAILER, $REDIS, $WEBHOOK);

    usort($accounts, function ($a, $b) {
        return strcmp($a->getGroupUID(), $b->getGroupUID());
    });

    foreach ($accounts as $pi_group) {
        echo "<tr>";
        echo "<td> <div class='type' style='width: 20px; margin: auto; border-radius: 5px; padding-left: 10px; 
        padding-right: 10px; text-align: center; font-size: 12px; color: white; background-color: " .
         '#800000' . ";'>" . $USER->getTypeNameFromSlug($types, $pi_group->getGroupType()) . "</div></td>";
        echo "<td style='text-align: center'>" . $pi_group->getGroupName() . "</td>";
        echo "<td style='text-align: center'>";
        echo
        "<form action='' method='POST' 
    onsubmit='return confirm(\"Are you sure you want to remove " . $pi_group->getGroupUID() . "?\")'>
        <input type='hidden' name='form_name' value='remGroup'>
        <input type='hidden' name='pi' value='" . $pi_group->getGroupUID() . "'>
        <button class='viewGroup' type='button'>View Group</button>
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

    $("button.viewGroup").click(function() {
        $pi = $(this).parent().parent().find("input[name='pi']").val();
        window.location.href = "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/view_group.php?group=" + $pi;
    });

    var ajax_url = "<?php echo $CONFIG["site"]["prefix"]; ?>/admin/ajax/get_group_members.php?group_uid=";
</script>

<?php
include $LOC_FOOTER;
