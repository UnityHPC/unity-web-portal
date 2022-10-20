<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($_POST["form_name"]) {
        case "viewAsUser":
            $_SESSION["viewUser"] = $_POST["uid"];
            UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel");
            break;
    }
}

include $LOC_HEADER;
?>

<h1>User Management</h1>
<hr>

<input type="text" id="tableSearch" placeholder="Search...">

<table class="searchable">
    <tr class="key">
        <td>Name</td>
        <td>Unity ID</td>
        <td>Org</td>
        <td>PIGroups</td>
        <td>Mail</td>
        <td>Actions</td>
    </tr>

    <?php
    $users = $LDAP->getAllUsers($SQL, $MAILER);

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user->getFirstname() . " " . $user->getLastname() . "</td>";
        echo "<td>" . $user->getUID() . "</td>";
        echo "<td>" . $user->getOrg() . "</td>";
        echo "<td>" . $user->getAllPIGroups() . "</td>";
        echo "<td><a href='mailto:" . $user->getMail() . "'>" . $user->getMail() . "</a></td>";
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
