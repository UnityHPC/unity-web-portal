<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UserFlag;

if (!$USER->getFlag(UserFlag::ADMIN)) {
    UnityHTTPD::forbidden("not an admin");
}

$getUserFromPost = function () {
    global $LDAP, $SQL, $MAILER, $WEBHOOK;
    return new UnityUser(UnityHTTPD::getPostData("uid"), $LDAP, $SQL, $MAILER, $WEBHOOK);
};

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    UnityHTTPD::validatePostCSRFToken();
    switch ($_POST["form_type"]) {
        case "req":
            $form_user = $getUserFromPost();
            if ($_POST["action"] == "Approve") {
                $group = $form_user->getPIGroup();
                $group->approveGroup($OPERATOR);
            } elseif ($_POST["action"] == "Deny") {
                $group = $form_user->getPIGroup();
                $group->denyGroup($OPERATOR);
            }
            break;
        case "reqChild":
            $form_user = $getUserFromPost();
            $parent_group = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $WEBHOOK);
            if ($_POST["action"] == "Approve") {
                $parent_group->approveUser($form_user);
            } elseif ($_POST["action"] == "Deny") {
                $parent_group->denyUser($form_user);
            }
            break;
        case "remUserChild":
            $form_user = $getUserFromPost();
            $parent = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $WEBHOOK);
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
    $requests = $SQL->getRequests(UnitySQL::REQUEST_BECOME_PI);

    foreach ($requests as $request) {
        $uid = $request["uid"];
        $request_user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $WEBHOOK);
        $name = $request_user->getFullname();
        $email = $request_user->getMail();
        echo "<tr>";
        echo "<td>$name</td>";
        echo "<td>$uid</td>";
        echo "<td><a href='mailto:$email'>$email</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request['timestamp'])) . "</td>";
        echo "<td>";
        $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
        echo
            "<form action='' method='POST'>
        $CSRFTokenHiddenFormInput
        <input type='hidden' name='form_type' value='req'>
        <input type='hidden' name='uid' value='$uid'>
        <input type='submit' name='action' value='Approve'
        onclick='return confirm(\"Are you sure you want to approve $uid?\");'>
        <input type='submit' name='action' value='Deny'
        onclick='return confirm(\"Are you sure you want to deny $uid?\");'>
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
    $owner_uids = $LDAP->getAllPIGroupOwnerUIDs();
    $owner_attributes = $LDAP->getUsersAttributes(
        $owner_uids,
        ["uid", "gecos", "mail"],
        ["gecos" => "(not found)", "mail" => "(not found)"]
    );
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

    var ajax_url = "<?php echo getURL("admin/ajax/get_group_members.php"); ?>?gid=";
</script>

<?php
require $LOC_FOOTER;
