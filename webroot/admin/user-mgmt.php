<?php

require_once "../../resources/autoload.php";

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($_POST["form_name"]) {
        case "viewAsUser":
            $_SESSION["viewUser"] = $_POST["uid"];
            $SITE->redirect($CONFIG["site"]["prefix"] . "/panel");
            break;
        default:
            $SITE->bad_request("invalid form_name '" . $_POST["form_name"] . "'");
    }
}

include $LOC_HEADER;
?>

<h1>User Management</h1>
<hr>

<!-- <input type="text" id="tableSearch" placeholder="Search..."> -->

<table class="searchable longTable sortable filterable">
    <tr class="key">
        <input type="text" style="margin-right:5px;" placeholder="Filter by..." id="common-filter" class="filterSearch">
        <td id="name"><span class="filter">⫧ </span>Name</td>
        <td id="uid"><span class="filter">⫧ </span>UID</td>
        <td id="org"><span class="filter">⫧ </span>Org</td>
        <td id="mail"><span class="filter">⫧ </span>Mail</td>
        <td id="groups"><span class="filter">⫧ </span>Groups</td>
        <td>Actions</td>
    </tr>

    <?php
    $users = $LDAP->getAllUsers($SQL, $MAILER, $REDIS, $WEBHOOK);

    usort($users, function ($a, $b) {
        return strcmp($a->getUID(), $b->getUID());
    });

    foreach ($users as $user) {
        if ($user->hasRequestedAccountDeletion()) {
            echo "<tr style='color:grey; font-style: italic'>";
        } else {
            echo "<tr>";
        }
        echo "<td>" . $user->getFirstname() . " " . $user->getLastname() . "</td>";
        echo "<td>" . $user->getUID() . "</td>";
        echo "<td>" . $user->getOrg() . "</td>";
        echo "<td><a href='mailto:" . $user->getMail() . "'>" . $user->getMail() . "</a></td>";
        echo "<td>";
        $cur_user_pi_groups = $user->getPIGroups();
        foreach ($cur_user_pi_groups as $cur_group) {
            echo "<a href='mailto:" . $cur_group->getOwner()->getMail() . "'>" . $cur_group->getPIUID() . "</a>";
            if ($cur_group !== array_key_last($cur_user_pi_groups)) {
                echo '<br>';
            }
        }
        echo "</td>";
        echo "<td>";
        echo "<form class='viewAsUserForm' action='' method='POST' 
        onsubmit='return confirm(\"Are you sure you want to switch to the user " . $user->getUID() . "?\");'>
        <input type='hidden' name='form_name' value='viewAsUser'>
        <input type='hidden' name='uid' value='" . $user->getUID() . "'>
        <input type='submit' name='action' value='Access'>
        </form>";
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>

<?php
include $LOC_FOOTER;
