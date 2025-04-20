<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;
use UnityWebPortal\lib\UnityGroup;

require_once $LOC_HEADER;

if ($USER->exists()) {
    UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/index.php");  // Redirect if account already exists
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = array();

    if (!isset($_POST["eula"]) || $_POST["eula"] != "agree") {
        // checkbox was not checked
        array_push($errors, "Accepting the EULA is required");
    }

    if ($_POST["new_user_sel"] == "not_pi") {
        $form_group = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $WEBHOOK);
        if (!$form_group->exists()) {
            array_push($errors, "The selected PI does not exist");
        }
    }

    // Request Account Form was Submitted
    if (count($errors) == 0) {
        if ($_POST["new_user_sel"] == "pi") {
            // requesting a PI account
            $USER->getPIGroup()->requestGroup($SEND_PIMESG_TO_ADMINS);
        } elseif ($_POST["new_user_sel"] == "not_pi") {
            $form_group->newUserRequest($USER);
        }
    }
}

?>

<h1>Request Account</h1>
<hr>

<form id="newAccountForm" action="" method="POST">
    <p>Please verify that the information below is correct before continuing</p>
    <div>
        <strong>Name&nbsp;&nbsp;</strong><?php echo $SSO["firstname"] . " " . $SSO["lastname"]; ?><br>
        <strong>Email&nbsp;&nbsp;</strong><?php echo $SSO["mail"]; ?>
    </div>
    <p>Your unity cluster username will be <strong><?php echo $SSO["user"]; ?></strong></p>

    <p>In order to activate your account on the Unity cluster, 
        you must join an existing PI group, or request your own PI group.</p>

    <hr>

    <?php
    $pending_requests = $SQL->getRequestsByUser($USER->getUID());
    if (count($pending_requests) > 0) {
        // already has pending requests
        echo "<p>Your request to activate your account has been submitted. 
		You will receive an email when your account is activated.</p>";
    } else {
        echo "<label><input type='radio' name='new_user_sel' value='pi'>Request a PI account (I am a PI)</label>";
        echo "<br>";
        echo "<label><input type='radio' name='new_user_sel' value='not_pi' checked>Join an existing PI group</label>";

        echo "<div style='position: relative;' id='piSearchWrapper'>";
        echo "<input type='text' id='pi_search' name='pi' placeholder='Search PI by NetID' required>";
        echo "<div class='searchWrapper' style='display: none;'></div>";
        echo "</div>";

        echo "<hr>";

        echo "<label><input type='checkbox' id='chk_eula' name='eula' value='agree' required>
		I have read and accept the <a target='_blank' href='" . $CONFIG["site"]["terms_of_service_url"] . "'>
		Unity Terms of Service</a></label>";

        echo "<br>";
        echo "<input style='margin-top: 10px;' type='submit' value='Request Account'>";
    }
    ?>

    <?php
    if (isset($errors)) {
        echo "<div class='message'>";
        foreach ($errors as $err) {
            echo "<p class='message-failure'>" . $err . "</p>";
        }
        echo "</div>";
    }
    ?>
</form>

<script>
    $('input[type=radio][name=new_user_sel]').change(function() {
        let pi_sel_text = $('#piSearchWrapper');
        if (this.value == 'not_pi') {
            pi_sel_text.show();
            $("#pi_search").prop("required", true);
        } else if (this.value == 'pi') {
            pi_sel_text.hide();
            $("#pi_search").prop("required", false);
        }
    });

    $("input[type=text][name=pi]").keyup(function() {
        var searchWrapper = $("div.searchWrapper");
        $.ajax({
            url: "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/pi_search.php?search=" + $(this).val(),
            success: function(result) {
                searchWrapper.html(result);

                if (result == "") {
                    searchWrapper.hide();
                } else {
                    searchWrapper.show();
                }
            }
        });
    });

    $("div.searchWrapper").on("click", "span", function(event) {
        var textBox = $("input[type=text][name=pi]");
        textBox.val($(this).html());
    });

    /**
     * Hides the searchresult box on click anywhere
     */
    $(document).click(function() {
        $("div.searchWrapper").hide();
    });
</script>

<?php
require_once $LOC_FOOTER;
