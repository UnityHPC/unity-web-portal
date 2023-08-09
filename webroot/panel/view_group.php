<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnitySite;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityPerms;

if (!isset($_GET["group"])) {
    die("Group ID not set");
}

$group = new UnityGroup($_GET["group"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modalErrors = array();
    $errors = array();

    if (isset($_POST["operated_on_uid"])) {
        echo "operated on uid set";
        $operated_on = new UnityUser($_POST["operated_on_uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        if (!$operated_on->exists()) {
            array_push($modalErrors, "This user doesn\'t exist");
        }
    }

    if (isset($_POST["uid"])) {
        $form_user = new UnityUser($_POST["uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    }

    if (isset($_POST['revoke_role']) && isset($_POST['revoke_uid'])) {
        $revoke_role = $_POST['revoke_role'];
        $revoke_uid = $_POST['revoke_uid'];
    }

    $unityPerms = new UnityPerms($SQL, $USER);

    switch ($_POST["form_name"]) {
        case "assignRoleForm":
            if (!$unityPerms->checkGrantRole($USER->getUID(), $group->getGroupUID(), $_COOKIE['role'])) {
                echo '<script>alert("You do not have permission to assign roles to this user")</script>';
                array_push($modalErrors, "You do not have permission to assign roles to this user");
            }

            if (empty($modalErrors)) {
                $group->assignRole($operated_on, $_COOKIE['role']);
                UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/view_group.php?group=" . $group->getGroupUID());
            }
            break;
        case "userReq":
            if ($_POST["action"] == "Approve") {
                if (!$unityPerms->checkApproveUser($USER->getUID(), $group->getGroupUID())) {
                    echo "<script>alert('You do not have permission to approve this user')</script>";
                }

                $group->approveUser($form_user);
            } elseif ($_POST["action"] == "Deny") {
                if (!$unityPerms->checkDenyUser($USER->getUID(), $group->getGroupUID())) {
                    echo "<script>alert('You do not have permission to deny this user')</script>";
                }

                $group->denyUser($form_user);
            }

            break;
        case "remUser":
            // remove user button clicked
            $group->removeUser($form_user);

            break;
        case "disband":
            $group->removeGroup();
            UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/account.php");

            break;
        case "revokeRole":
            if ($revoke_uid == $USER->getUID()) {
                echo "<script>alert('You cannot revoke your own roles')</script>";
            } else {
                if (!$unityPerms->checkRevokeRole($USER->getUID(), $group->getGroupUID(), $revoke_role)) {
                    echo "<script>alert('You do not have permission to revoke this role')</script>";
                } else {
                    $group->revokeRole($revoke_uid, $revoke_role);
                    UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/view_group.php?group=" . $group->getGroupUID());
                }
            }
    }
}

include $LOC_HEADER;
?>

<h1>Group Details</h1>
<hr>

<?php
$requests = $group->getRequests();
$assocs = $group->getGroupMembers();

if (count($requests) + count($assocs) == 1) {
    echo "<p>You do not have any users attached to your PI account. 
    Ask your users to request to join your account on the <a href='" . $CONFIG["site"]["prefix"] .
    "/panel/groups.php'>My Groups</a> page.</p>";
}

if (count($requests) > 0 && ($USER->hasPermission($_GET["group"], "unity.approve_user") || $USER->hasPermission($_GET["group"], "unity.deny_user"))) {
    echo "<h5>Pending Requests</h5>";
    echo "<table>";

    foreach ($requests as $request) {
        echo "<tr>";
        echo "<td>" . $request[0]->getFirstname() . " " . $request[0]->getLastname() . "</td>";
        echo "<td>" . $request[0]->getUID() . "</td>";
        echo "<td><a href='mailto:" . $request[0]->getMail() . "'>" . $request[0]->getMail() . "</a></td>";
        echo "<td>" . date("jS F, Y", strtotime($request[1])) . "</td>";
        echo "<td>";
        if ($USER->hasPermission($_GET["group"], "unity.approve_user")) {
            echo
            "<form action='' method='POST'>
            <input type='hidden' name='form_name' value='userReq'>
            <input type='hidden' name='uid' value='" . $request[0]->getUID() . "'>
            <input type='submit' name='action' value='Approve' 
            onclick='return confirm(\"Are you sure you want to approve " . $request[0]->getUID() . "?\")'>
            </form>";
        }
        if ($USER->hasPermission($_GET["group"], "unity.deny_user")) {
            echo
            "<form action='' method='POST'>
            <input type='hidden' name='form_name' value='userReq'>
            <input type='hidden' name='uid' value='" . $request[0]->getUID() . "'>
            <input type='submit' name='action' value='Deny'
            onclick='return confirm(\"Are you sure you want to deny " . $request[0]->getUID() . "?\")'>
            </form>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    if (count($assocs) > 0) {
        echo "<hr>";
    }
}

echo "<h5>Users in Group</h5>";

echo "<table>";

foreach ($assocs as $assoc) {
    if ($assoc->getUID() == $USER->getUID()) {
        continue;
    }

    echo "<tr>";
    if ($USER->hasPermission($_GET["group"], "unity.admin")) {
        echo "<td>";
        echo
        "<form action='' method='POST'>
        <input type='hidden' name='form_name' value='remUser'>
        <input type='hidden' name='uid' value='" . $assoc->getUID() . "'>
        <input type='submit' value='Remove'
        onclick='return confirm(\"Are you sure you want to remove " . $assoc->getUID() . " from your group?\")'>
        </form>";
        echo "</td>";
    }
    echo "<td>" . $assoc->getFirstname() . " " . $assoc->getLastname() . "</td>";
    echo "<td>" . $assoc->getUID() . "</td>";
    echo "<td><a href='mailto:" . $assoc->getMail() . "'>" . $assoc->getMail() . "</a></td>";
    echo "<td> <div class='type' style='border-radius: 10px; padding-left: 10px; padding-right: 10px; text-align: center; font-size: 12px; color: white; background-color: " . '#800000' . ";'>" . $assoc->getGroupRoles($_GET["group"])[0] . "</div></td>";
    echo "</tr>";
}

echo "</table>";

if ($USER->hasPermission($_GET["group"], "unity.grant_role") || $USER->hasPermission($_GET["group"], "unity.revoke_role")) {
    $roles = $group->getAvailableRoles();

    echo "<br>";
    echo "<h2>Manage Roles</h2>";
    echo "<hr>";

    foreach ($roles as $role) {
        $users_with_role = $group->getUsersWithRole($role["slug"]);

        echo "<h5>" . $role["display_name"] . " (" . count($users_with_role) . ") </h5>";

        foreach ($users_with_role as $user) {
            echo "<table>";
            echo "<tr>";
            if ($USER->hasPermission($_GET["group"], "unity.admin") || $USER->hasPermission($_GET["group"], "unity.revoke_role")) {
                echo "<td>";
                echo
                "<form action='' method='POST'>
                <input type='hidden' name='form_name' value='revokeRole'>
                <input type='hidden' name='revoke_uid' value='" . $user->getUID() . "'>
                <input type='hidden' name='revoke_role' value='" . $role["slug"] . "'>
                <input type='submit' value='Revoke'
                onclick='return confirm(\"Are you sure you want to revoke the role from " . $user->getUID() . "?\")'>
                </form>";
                echo "</td>";
            }
            echo "<td>" . $user->getFirstname() . " " . $user->getLastname() . "</td>";
            echo "<td>" . $user->getUID() . "</td>";
            echo "<td><a href='mailto:" . $user->getMail() . "'>" . $user->getMail() . "</a></td>";
            echo "</tr>";
            echo "</table>";
        }
        echo "<div>";
        echo "<input type='hidden' name='role' value='" . $role["slug"] . "'>";
        echo "<button type='button' class='plusBtn btnAssignRole' style='font-size: 13px; padding-top: 7px; padding-bottom: 7px; margin-bottom: 20px;'>Assign " . $role["display_name"] . " Role</button>";
        echo "</div>";
    }
}

if ($USER->hasPermission($_GET["group"], "unity.admin")) {
    echo "<br>";
    echo "<h2>Manage Group</h2>";
    echo "<hr>";
    echo "<h5>Danger Zone</h5>";
    echo
    "<form action='' method='POST' onsubmit='return confirm(\"Are you sure you want to disband your group?\")'>
    <input type='hidden' name='form_name' value='disband'>
    <input type='submit' value='Disband Group'>
    </form>
    ";
}
?>
</table>

<script>
    $("button.btnAssignRole").click(function() {
        const role = $(this).parent().find("input[name='role']").val();
        console.log(role);
        document.cookie = "role=" + role;
        const roleName = $(this).text();
        document.cookie = "roleName=" + $(this).text();
        openModal(roleName, "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/assign_role.php?group=<?php echo $_GET["group"]; ?>");
    });

    <?php
        // This is here to re-open the modal if there are errors
        if (isset($modalErrors) && is_array($modalErrors) && count($modalErrors) > 0) {
            $errorHTML = "";
            foreach ($modalErrors as $error) {
                $errorHTML .= "<span>$error</span>";
            }
            $roleName = $_COOKIE["roleName"];
            echo "openModal(" . $roleName . ", '" .
            $CONFIG["site"]["prefix"] . "/panel/modal/assign_role.php', '" . $errorHTML . "');";
        }
    ?>
</script>

<?php
include $LOC_FOOTER;
