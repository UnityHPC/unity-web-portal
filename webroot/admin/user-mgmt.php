<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;

if (!$USER->isAdmin()) {
    UnityHTTPD::forbidden("not an admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    UnityHTTPD::validatePostCSRFToken();
    switch ($_POST["form_type"]) {
        case "viewAsUser":
            $_SESSION["viewUser"] = $_POST["uid"];
            UnityHTTPD::redirect(CONFIG["site"]["prefix"] . "/panel/account.php");
            break;
    }
}

require $LOC_HEADER;
?>

<h1>User Management</h1>
<hr>

<!-- <input type="text" id="tableSearch" placeholder="Search..."> -->

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
        <td id="uid"><span class="filter">⫧ </span>UID</td>
        <td id="org"><span class="filter">⫧ </span>Org</td>
        <td id="mail"><span class="filter">⫧ </span>Mail</td>
        <td id="groups"><span class="filter">⫧ </span>Groups</td>
        <td>Actions</td>
    </tr>

    <?php
    $UID2PIGIDs = $LDAP->getQualifiedUID2PIGIDs();
    $user_attributes = $LDAP->getQualifiedUsersAttributes(
        ["uid", "gecos", "o", "mail"],
        default_values: ["gecos" => "(not found)", "o" => "(not found)", "mail" => "(not found)"]
    );
    usort($user_attributes, fn ($a, $b) => strcmp($a["uid"][0], $b["uid"][0]));
    foreach ($user_attributes as $attributes) {
        $uid = $attributes["uid"][0];
        if ($SQL->accDeletionRequestExists($uid)) {
            echo "<tr style='color:grey; font-style: italic'>";
        } else {
            echo "<tr>";
        }
        echo "<td>" . $attributes["gecos"][0] . "</td>";
        echo "<td>" . $uid . "</td>";
        echo "<td>" . $attributes["o"][0] . "</td>";
        echo "
            <td>
                <a href='mailto:" . $attributes["mail"][0] . "'>" . $attributes["mail"][0] . "</a>
            </td>
        ";
        echo "<td>";
        if (count($UID2PIGIDs[$uid]) > 0) {
            echo "<table>";
            foreach ($UID2PIGIDs[$uid] as $gid) {
                echo "<tr><td>$gid</td></tr>";
            }
            echo "</table>";
        }
        echo "</td>";
        echo "<td>";
        $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
        echo "<form class='viewAsUserForm' action='' method='POST'
        onsubmit='return confirm(\"Are you sure you want to switch to the user $uid?\");'>
        $CSRFTokenHiddenFormInput
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
require $LOC_FOOTER;
