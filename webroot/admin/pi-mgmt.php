<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;

if (!$USER->isAdmin()) {
    UnityHTTPD::forbidden("not an admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    }

    switch ($_POST["form_type"]) {
        case "req":
            if ($_POST["action"] == "Approve") {
                $group = $form_user->getPIGroup();
                $group->approveGroup($OPERATOR);
            } elseif ($_POST["action"] == "Deny") {
                $group = $form_user->getPIGroup();
                $group->denyGroup($OPERATOR);
            }

            break;
        case "reqChild":
            $parent_group = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            if ($_POST["action"] == "Approve") {
                $parent_group->approveUser($form_user);
            } elseif ($_POST["action"] == "Deny") {
                $parent_group->denyUser($form_user);
            }

            break;
        case "remUserChild":
            $parent = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            $parent->removeUser($form_user);

            break;
    }
}

require $LOC_HEADER;
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
        $uid = $request["uid"];
        echo "<tr>";
        echo "<td>" . $request["firstname"] . " " . $request["lastname"] . "</td>";
        echo "<td>" . $request["uid"] . "</td>";
        echo "<td><a href='mailto:" . $request["email"] . "'>" . $request["email"] . "</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request['timestamp'])) . "</td>";
        echo "<td>";
        echo
            "<form action='' method='POST'>
        <input type='hidden' name='form_type' value='req'>
        <input type='hidden' name='uid' value='" . $request["uid"] . "'>
        <input
          type='submit' name='action' value='Approve'
          onclick='
            confirm(\"Are you sure you want to approve $uid?\")
            && this.form.submit()
            && this.disabled=true;
          '
        >
        <input
          type='submit' name='action' value='Deny'
          onclick='
            confirm(\"Are you sure you want to deny $uid?\")
            && this.form.submit()
            && this.disabled=true;
          '
        >
        </form>";
        echo "</td>";
        echo "</tr>";
    }
    ?>

</table>

<h5>List of PIs</h5>

<table class="searchable longTable sortable filterable">
    <tr class="key">
        <input
            type="text"
            style="margin-right:5px;"
            placeholder="Filter by..."
            id="common-filter"
            class="filterSearch"
        >
        <td id="name"><span class="filter">⫧ </span>Name</td>
        <td id="unityID"><span class="filter">⫧ </span>Unity ID</td>
        <td id="mail"><span class="filter">⫧ </span>Mail</td>
        <td>Actions</td>
    </tr>

    <?php
    $owner_attributes = $LDAP->getAllPIGroupOwnerAttributes(["uid", "gecos", "mail"]);
    usort($owner_attributes, fn($a, $b) => strcmp($a["uid"][0], $b["uid"][0]));
    foreach ($owner_attributes as $attributes) {
        $mail = $attributes["mail"][0];
        echo "<tr class='expandable'>";
        echo "<td><button class='btnExpand'>&#9654;</button>" . $attributes["gecos"][0] . "</td>";
        echo "<td>" . UnityGroup::OwnerUID2GID($attributes["uid"][0]) . "</td>";
        echo "<td><a href='mailto:$mail'>$mail</a></td>";
        echo "</tr>";
    }
    ?>
</table>

<script>
    $("table tr.tr-pichild").hide(); // Hide the children first (and then the women)

    $("table tr").click(function () {
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

    var ajax_url = "<?php echo CONFIG["site"]["prefix"]; ?>/admin/ajax/get_group_members.php?gid=";
</script>

<?php
require $LOC_FOOTER;
