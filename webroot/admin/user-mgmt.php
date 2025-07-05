<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;

if (!$USER->isAdmin()) {
    UnitySite::forbidden("not an admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($_POST["form_type"]) {
        case "viewAsUser":
            $_SESSION["viewUser"] = $_POST["uid"];
            UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/account.php");
            break;
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
    $UID2PIGIDs = $LDAP->getAllUID2PIGIDs();
    $user_entries = $LDAP->getAllUsersEntries();
    usort($user_entries, function ($a, $b) {
        return strcmp($a["uid"], $b["uid"]);
    });
    foreach ($user_entries as $entry) {
        $uid = $entry["uid"];
        if ($SQL->accDeletionRequestExists($uid)) {
            echo "<tr style='color:grey; font-style: italic'>";
        } else {
            echo "<tr>";
        }
        echo "<td>" . $entry["gecos"] . "</td>";
        echo "<td>" . $uid . "</td>";
        echo "<td>" . $user["o"] . "</td>";
        echo "<td><a href='mailto:" . $user["mail"] . "'>" . $user["mail"] . "</a></td>";
        echo "<td>";
        foreach ($UID2PIGIDS[$uid] as $GID) {
            echo "<p>$GID</p>";
        }
        echo "<br>";
        echo "</td>";
        echo "<td>";
        echo "<form class='viewAsUserForm' action='' method='POST'
        onsubmit='return confirm(\"Are you sure you want to switch to the user '$uid'?\");'>
        <input type='hidden' name='form_type' value='viewAsUser'>
        <input type='hidden' name='uid' value='$uid'>
        <input type='submit' name='action' value='Access'>
        </form>";
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>

<?php
include $LOC_FOOTER;
