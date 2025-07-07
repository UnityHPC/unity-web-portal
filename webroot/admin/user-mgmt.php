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
    $user_arrays = $LDAP->getAllUsersArrays(["uid", "gecos", "o", "mail"]);
    usort($user_arrays, fn ($a, $b) => strcmp($a["uid"][0], $b["uid"][0]));
    foreach ($user_arrays as $array) {
        $uid = $array["uid"][0];
        if ($SQL->accDeletionRequestExists($uid)) {
            echo "<tr style='color:grey; font-style: italic'>";
        } else {
            echo "<tr>";
        }
        echo "<td>" . $array["gecos"][0] . "</td>";
        echo "<td>" . $uid . "</td>";
        echo "<td>" . $array["o"][0] . "</td>";
        echo "<td><a href='mailto:" . $array["mail"][0] . "'>" . $array["mail"][0] . "</a></td>";
        echo "<td>";
        foreach ($UID2PIGIDs[$uid] as $gid) {
            echo "<p>$gid</p>";
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
