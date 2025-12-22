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

<div id="columnToggle" style="margin-bottom: 10px;">
    <label><input type="checkbox" class="col-toggle" checked> Name</label>
    <label><input type="checkbox" class="col-toggle" checked> UID</label>
    <label><input type="checkbox" class="col-toggle" checked> Org</label>
    <label><input type="checkbox" class="col-toggle" checked> Mail</label>
    <label><input type="checkbox" class="col-toggle" checked> Groups</label>
    <label><input type="checkbox" class="col-toggle" checked> Actions</label>
    <?php
    foreach (UserFlag::cases() as $flag) {
        $value = $flag->value;
        echo "<label><input type='checkbox' class='col-toggle' checked> $value</label>";
    }
    ?>
</div>

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
        <?php
        foreach (UserFlag::cases() as $flag) {
            $value = $flag->value;
            echo "<th id='$value'><span class='filter'>⫧ </span>$value</th>";
        }
        ?>
    </tr>

    <?php
    $UID2PIGIDs = $LDAP->getUID2PIGIDs();
    $user_attributes = $LDAP->getAllUsersAttributes(
        ["uid", "gecos", "o", "mail"],
        default_values: [
            "gecos" => ["(not found)"],
            "o" => ["(not found)"],
            "mail" => ["(not found)"]
        ]
    );
    $users_with_flags = [];
    foreach (UserFlag::cases() as $flag) {
        $users_with_flags[$flag->value] = $LDAP->userFlagGroups[$flag->value]->getMemberUIDs();
    }
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
        if (array_key_exists($uid, $UID2PIGIDs) && ($UID2PIGIDs[$uid]) > 0) {
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
        foreach (UserFlag::cases() as $flag) {
            echo "<td>";
            if (in_array($uid, $users_with_flags[$flag->value])) {
                echo $flag->value;
            }
            echo "</td>";
        }
        echo "</tr>";
    }
    ?>
</table>

<script>
    const columnToggleStyle = document.createElement('style');
    columnToggleStyle.id = 'columnToggleStyles';
    document.head.appendChild(columnToggleStyle);
    document.querySelectorAll('.col-toggle').forEach((checkbox, index) => {
        const col = index + 1;
        checkbox.addEventListener('change', function() {
            const rule = `tr > :nth-child(${col}) { display: none !important; }`;
            const styles = columnToggleStyle.sheet;
            if (this.checked) {
                for (let i = styles.cssRules.length - 1; i >= 0; i--) {
                    if (styles.cssRules[i].selectorText === `tr > :nth-child(${col})`) {
                        styles.deleteRule(i);
                    }
                }
            } else {
                styles.insertRule(rule);
            }
            console.log(JSON.stringify(Array.from(styles.cssRules).map(rule => rule.selectorText)));
        });
    });
</script>

<?php
require $LOC_FOOTER;
