<?php

require_once "../../resources/autoload.php";


use UnityWebPortal\lib\UnityGroup;

require_once $LOC_HEADER;

$types = $USER->getRequestableGroupTypes();
$pending_requests = $USER->getPendingGroupRequests();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $group_type_value = $_POST['group_type'];
    $group_type_values = explode("-", $group_type_value);
    $group_type_prefix = $group_type_values[0];
    $group_type_slug = $group_type_values[1];
    $group_type_time_limited = $group_type_values[2];
    $group_type_isNameable = $group_type_values[3];
    $group_type_exclusiveOwner = $group_type_values[4];

    if ($group_type_exclusiveOwner == 1) {
        $groups = $USER->getGroups(true);
        $group_names = array();
        foreach ($groups as $group) {
            array_push($group_names, $group->getGroupUID());
        }
        $uid = $USER->getUID();
        foreach ($group_names as $group_name) {
            if (strpos($group_name, $uid) !== false) {
                $group_type = explode("_", $group_name)[0];
                if ($group_type == $group_type_slug) {
                    echo "<script>alert('You already have a group of this type.');</script>";
                    // refresh page
                    header("Refresh:0");
                }
            }
        }
    }

    if ($group_type_time_limited == 1) {
        $group_start_date = $_POST['group_start_date'];
        $group_end_date = $_POST['group_end_date'];
    } else {
        $group_start_date = null;
        $group_end_date = null;
    }

    if ($_POST['group_name'] == "") {
        $group_name = $USER->getUID();
    } else {
        $group_name = $_POST['group_name'];
    }

    $group_uid = $group_type_prefix . $group_name;

    $new_group = new UnityGroup($group_uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    $new_group->requestGroup(
        $USER->getUID(),
        $group_type_slug,
        $group_name,
        $SEND_PIMESG_TO_ADMINS,
        $group_start_date,
        $group_end_date
    );
    header("Refresh:0");
}

?>

<h1>Request New Group</h1>
<hr>

<?php
if (count($pending_requests) > 0) {
    echo "<h5>Pending Requests</h5>";
    echo "<table>";
    foreach ($pending_requests as $request) {
        $requested_owner = $USER;
        echo "<tr class='pending_request'>";
        echo "<td> 
        <div class='type' style='border-radius: 5px; padding-left: 10px; color: white; 
        padding-right: 10px; text-align: center; font-size: 12px; 
        background-color: #800000'>" . $USER->getTypeNameFromSlug($types, $request['group_type']) . "</div></td>";
        echo "<td>" . $request['group_name'] . "</td>";
        echo "<td>" . date("jS F, Y", strtotime($request['requested_on'])) . "</td>";
        echo "<td></td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
}
?>

<form id="newGroupForm" action="" method="POST">
    <p>Fill in the following information to request a new group</p>
    <div>
        <strong>Type of Group</strong><br>
        <?php
        foreach ($types as $type) {
            if ($type['slug'] == 'pi') {
                echo "<label><input type='radio' name='group_type' 
                value='" . $type["prefix"] . "-" . $type["slug"] . "-" .
                $type['time_limited'] . "-" . $type['isNameable'] . "-" . $type['exclusiveOwner'] . "' checked> " .
                $type["name"] . "</label><br>";
            } else {
                echo "<label><input type='radio' name='group_type' 
                value='" . $type["prefix"] . "-" . $type["slug"] . "-" .
                $type['time_limited'] . "-" . $type['isNameable'] . "-" . $type['exclusiveOwner'] . "'> "
                . $type["name"] . "</label><br>";
            }
        }
        ?>
        <div id="dateSelector" style="display: none; margin-top: 15px">
            <strong>Select start and end dates</strong><br>
            <label>Start Date: &nbsp;&nbsp;<input type='date' name='group_start_date'></label><br>
            <label>End Date: &nbsp;&nbsp;<input type='date' name='group_end_date'></label><br>
        </div>
        <div id="nameInputBox" style="margin-top: 10px;">
            <strong>Name (cannot have spaces)&nbsp;&nbsp;</strong><br>
            <input type="text" name="group_name" placeholder="Name of Group" style="margin-bottom: 15px"><br>
            <div style="color: red; font-size: 0.8rem; display: none; margin-top: -10px;" 
            id="groupNameError">Error Occured<br></div>
        </div>
    </div>
    <input style='margin-top: 10px;' type='submit' value='Request Group' id="requestGroupButton">
</form>

<script>
    $(window).on("load", function() {
        let type_info = $('input[type=radio][name=group_type]:checked').val().split('-');
        const isNameable = type_info[3];
        const time_limited = type_info[2];
        let date_selector = document.getElementById('dateSelector');
        if (time_limited == 1) {
            date_selector.style.display = 'block';
        } else if (time_limited == 0) {
            date_selector.style.display = 'none';
        }
        let nameInputBox = document.getElementById('nameInputBox');
        if (isNameable == 1) {
            nameInputBox.style.display = 'block';
        } else if (isNameable == 0) {
            nameInputBox.style.display = 'none';
        }
    })

    $('input[type=radio][name=group_type]').change(function() {
        let type_info = this.value.split('-');
        const isNameable = type_info[3];
        const time_limited = type_info[2];
        let date_selector = document.getElementById('dateSelector');
        if (time_limited == 1) {
            $("#requestGroupButton").prop("disabled", true);
            date_selector.style.display = 'block';
        } else if (time_limited == 0) {
            $("#requestGroupButton").prop("disabled", false);
            date_selector.style.display = 'none';
        }
        let nameInputBox = document.getElementById('nameInputBox');
        if (isNameable == 1) {
            $("#requestGroupButton").prop("disabled", true);
            nameInputBox.style.display = 'block';
        } else if (isNameable == 0) {
            $("#requestGroupButton").prop("disabled", false);
            nameInputBox.style.display = 'none';
        }
    });

    $("input[type=text][name=group_name]").keyup(function() {
        $group_name = $(this).val();
        $span = $("#groupNameError");
        if ($group_name.length == 0) {
            $("#requestGroupButton").prop("disabled", true);
        } else {
            if ($group_name.includes(" ")) {
                $span.text("Invalid name. Make sure to not have spaces.");
                $span.show();
                $("#requestGroupButton").prop("disabled", true);
            } else {
                $span.hide();
                $.ajax({url: "<?php echo $CONFIG["site"]["prefix"] ?>/panel/ajax/check_group_name.php?group_name="
                + $(this).val(), success: function(result) {
                    if (result == "not available") {
                        $span.text("Name not available. Try something different.");
                        $span.show();
                        $("#requestGroupButton").prop("disabled", true);
                    } else {
                        $span.hide();
                        $("#requestGroupButton").prop("disabled", false);
                    }
                }});
            }
        }
    });

</script>

<?php
require_once $LOC_FOOTER;