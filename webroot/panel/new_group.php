<?php

require_once "../../resources/autoload.php";


use UnityWebPortal\lib\UnityGroup;

require_once $LOC_HEADER;

$types = $USER->getRequestableGroupTypes();
$pending_requests = $USER->getPendingGroupRequests();

function getTypeNameFromSlug($slug) {
    global $types;
    foreach ($types as $type) {
        if ($type['slug'] == $slug) {
            return $type['name'];
        }
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $group_type_value = $_POST['group_type'];
    $group_type_values = explode("-", $group_type_value);
    $group_type_prefix = $group_type_values[0];
    $group_type_slug = $group_type_values[1];
    $group_type_time_limited = $group_type_values[2];
    
    if ($group_type_time_limited == 1) {
        $group_start_date = $_POST['group_start_date'];
        $group_end_date = $_POST['group_end_date'];
    } else {
        $group_start_date = null;
        $group_end_date = null;
    }

    $group_uid = $group_type_prefix . $_POST['group_name'];

    $new_group = new UnityGroup($group_uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    $new_group->requestGroup($USER->getUID(), $group_type_slug, $_POST['group_name'], $SEND_PIMESG_TO_ADMINS, $group_start_date, $group_end_date);
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
        <div class='type' style='border-radius: 5px; padding-left: 10px; color: white; padding-right: 10px; text-align: center; font-size: 12px; background-color: #800000'>" . getTypeNameFromSlug($request['group_type']) . "</div></td>";
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
        <strong>Name (cannot have spaces)&nbsp;&nbsp;</strong><br>
        <input type="text" name="group_name" placeholder="name_of_the_group" required  style="margin-bottom: 15px"><br>
        <div style="color: red; font-size: 0.8rem; display: none; margin-top: -10px;" id="groupNameError">(Name not available. Try something different)/(Invalid name. Make sure to not have spaces)<br></div>
        <strong>Type of Group</strong><br>
        <?php
        foreach ($types as $type) {
            if ($type['slug'] == 'pi') {
                echo "<label><input type='radio' name='group_type' value='" . $type["prefix"] . "-" . $type["slug"] . "-" . $type['time_limited'] . "' checked> " . $type["name"] . "</label><br>";
            } else {
                echo "<label><input type='radio' name='group_type' value='" . $type["prefix"] . "-" . $type["slug"] . "-" . $type['time_limited'] . "'> " . $type["name"] . "</label><br>";
            }
        }
        ?>
        <div id="dateSelector" style="display: none; margin-top: 15px">
            <strong>Select start and end dates</strong><br>
            <label>Start Date: &nbsp;&nbsp;<input type='date' name='group_start_date'></label><br>
            <label>End Date: &nbsp;&nbsp;<input type='date' name='group_end_date'></label><br>
        </div>
    </div>
    <input style='margin-top: 10px;' type='submit' value='Request Group'>
</form>

<script>
    $('input[type=radio][name=group_type]').change(function() {
        let date_selector = document.getElementById('dateSelector');
        if (this.value.endsWith('1')) {
            date_selector.style.display = 'block';
        } else if (this.value.endsWith('0')) {
            date_selector.style.display = 'none';
        }
    });


    $("input[type=text][name=group_name]").keyup(function() {
        $group_name = $(this).val();
        $span = $("#groupNameError");
        if ($group_name.includes(" ")) {
            $span.text("Invalid name. Make sure to not have spaces.");
            $span.show();
        } else {
            $span.hide();
            $.ajax({url: "<?php echo $CONFIG["site"]["prefix"] ?>/panel/ajax/check_group_name.php?group_name="
            + $(this).val(), success: function(result) {
                if (result == "not available") {
                    $span.text("Name not available. Try something different.");
                    $span.show();
                } else {
                    $span.hide();
                }
            }});   
        }
    });
</script>

<?php
require_once $LOC_FOOTER;
