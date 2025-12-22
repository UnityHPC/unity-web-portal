<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UserFlag;

if (!$USER->getFlag(UserFlag::ADMIN)) {
    UnityHTTPD::forbidden("not an admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    UnityHTTPD::validatePostCSRFToken();
    switch ($_POST["form_type"]) {
        case "viewAsUser":
            $_SESSION["viewUser"] = $_POST["uid"];
            UnityHTTPD::redirect(getURL("panel/account.php"));
            break; /** @phpstan-ignore deadCode.unreachable */
    }
}

require $LOC_HEADER;
?>

<h1>User Management</h1>
<hr>

<!-- <input type="text" id="tableSearch" placeholder="Search..."> -->

<table class="searchable longTable sortable filterable">
    <tr>
        <input
            type="text"
            style="margin-right:5px;"
            placeholder="Filter by..."
            id="common-filter"
            class="filterSearch"
        >
        <th id="name"><span class="filter">⫧ </span>Name</th>
        <th id="uid"><span class="filter">⫧ </span>UID</th>
        <th id="org"><span class="filter">⫧ </span>Org</th>
        <th id="mail"><span class="filter">⫧ </span>Mail</th>
        <th id="groups"><span class="filter">⫧ </span>Groups</th>
        <th>Actions</th>
    </tr>

    <?php
    $UID2PIGIDs = $LDAP->getQualifiedUID2PIGIDs();
    $user_attributes = $LDAP->getQualifiedUsersAttributes(
        ["uid", "gecos", "o", "mail"],
        default_values: [
            "gecos" => ["(not found)"],
            "o" => ["(not found)"],
            "mail" => ["(not found)"]
        ]
    );
    usort($user_attributes, fn ($a, $b) => strcmp($a["uid"][0], $b["uid"][0]));
    foreach ($user_attributes as $attributes) {
        $uid = $attributes["uid"][0];
        $uid_escaped = htmlspecialchars($uid);
        $gecos = htmlspecialchars($attributes["gecos"][0]);
        $org = htmlspecialchars($attributes["o"][0]);
        $mail_link = "mailto:" . urlencode($attributes["mail"][0]);
        $mail_display = htmlspecialchars($attributes["mail"][0]);
        if ($SQL->accDeletionRequestExists($uid)) {
            echo "<tr style='color:grey; font-style: italic'>";
        } else {
            echo "<tr>";
        }
        echo "<td>$gecos</td>";
        echo "<td>$uid_escaped</td>";
        echo "<td>$org</td>";
        echo "<td><a href='$mail_link'>$mail_display</a></td>";
        echo "<td>";
        if (count($UID2PIGIDs[$uid]) > 0) {
            echo "<table>";
            foreach ($UID2PIGIDs[$uid] as $gid) {
                echo "<tr><td>" . htmlspecialchars($gid) . "</td></tr>";
            }
            echo "</table>";
        }
        echo "</td>";
        echo "<td>";
        $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
        echo "<form class='viewAsUserForm' action='' method='POST'
        onsubmit='return confirm(\"Are you sure you want to switch to the user $uid_escaped?\");'>
        $CSRFTokenHiddenFormInput
        <input type='hidden' name='form_type' value='viewAsUser'>
        <input type='hidden' name='uid' value='$uid_escaped'>
        <input type='submit' name='action' value='Access'>
        </form>";
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>

<?php
require $LOC_FOOTER;
