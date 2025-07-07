<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnitySQL;

if ($USER->exists()) {
    UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/account.php");
}

$pending_requests = $SQL->getRequestsByUser($USER->uid);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["new_user_sel"])) {
        if (!isset($_POST["eula"]) || $_POST["eula"] != "agree") {
            UnitySite::badRequest("user did not agree to EULA");
        }
        if ($USER->uid != $SSO["user"]) {
            $sso_user = $SSO["user"];
            UnitySite::badRequest(
                "cannot request due to uid mismatch: USER='{$USER->uid}' SSO[user]='$sso_user'"
            );
        }
        if ($_POST["new_user_sel"] == "not_pi") {
            $form_group = new UnityGroup($_POST["pi"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
            if (!$form_group->exists()) {
                UnitySite::badRequest("The selected PI does not exist");
            }
            $form_group->newUserRequest(
                $USER,
                $SSO["firstname"],
                $SSO["lastname"],
                $SSO["mail"],
                $SSO["org"]
            );
        }
        if ($_POST["new_user_sel"] == "pi") {
            if (!isset($_POST["confirm_pi"]) || $_POST["confirm_pi"] != "agree") {
                UnitySite::badRequest("user did not agree to account policy");
            }
            $USER->getPIGroup()->requestGroup(
                $SSO["firstname"],
                $SSO["lastname"],
                $SSO["mail"],
                $SSO["org"],
                $SEND_PIMESG_TO_ADMINS
            );
        }
    } elseif (isset($_POST["cancel"])) {
        foreach ($pending_requests as $request) {
            if ($request["request_for"] == "admin") {
                $USER->getPIGroup()->cancelGroupRequest();
            } else {
                $pi_group = new UnityGroup($request["request_for"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
                $pi_group->cancelGroupJoinRequest($user = $USER);
            }
        }
    }
}
include $LOC_HEADER;
?>

<h1>Request Account</h1>
<hr>

<?php if (count($pending_requests) > 0) : ?>
    <p>You have pending account activation requests:</p>
    <?php foreach ($pending_requests as $request) : ?>
        <ul>
            <li>
                <?php
                $gid = $request["request_for"];
                if ($gid == UnitySQL::REQUEST_BECOME_PI) {
                    $group_uid = $USER->getPIGroup()->gid;
                    echo "<p>Ownership of PI Account/Group: <code>$group_uid</code> </p>";
                } else {
                    $owner_uid = UnityGroup::GID2OwnerUID($gid);
                    echo "<p>Membership in PI Group owned by: <code>$owner_uid</code></p>";
                }
                ?>
            </li>
        </ul>
        <hr>
        <p><strong>Requesting Ownership of PI Account/Group</strong></p>
        <p>You will receive an email when your account has been approved.</p>
        <p>
            <?php
            $addr = $CONFIG['mail']['support'];
            $name = $CONFIG['mail']['support_name'];
            echo "Email <a href='mailto:$addr'>$name</a> if you have not heard back in one business day.";
            ?>
        </p>
        <br>
        <p><strong>Requesting Membership in a PI Group</strong></p>
        <p>You will receive an email when your account has been approved by the PI.</p>
        <p>You may need to remind them.</p>
        <hr>
        <form action="" method="POST">
            <input name="cancel" style='margin-top: 10px;' type='submit' value='Cancel Request' />
        </form>
    <?php endforeach; ?>
<?php else : ?>
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

        <label><input type='radio' name='new_user_sel' value='pi'>Request a PI account</label>
        <label><input type='radio' name='new_user_sel' value='not_pi' checked>Join an existing PI group</label>

        <div style='position: relative;' id='piSearchWrapper'>
            <input type='text' id='pi_search' name='pi' placeholder='Search PI by NetID' required>
            <div class='searchWrapper' style='display: none;'></div>
        </div>

        <hr>

        <div style='position: relative;display: none;' id='piConfirmWrapper'>
            <label><input type='checkbox' id='chk_pi' name='confirm_pi' value='agree'>
                I have read the PI <a href="<?php echo $CONFIG["site"]["account_policy_url"]; ?>">
                    account policy</a> guidelines. </label>
        </div>
        <br>

        <label><input type='checkbox' id='chk_eula' name='eula' value='agree' required>
            I have read and accept the <a target='_blank' href='<?php echo $CONFIG["site"]["terms_of_service_url"]; ?>'>
                Unity Terms of Service</a>.</label>

        <br>
        <input style='margin-top: 10px;' type='submit' value='Request Account'>
    </form>
<?php endif; ?>

<script>
    $('input[type=radio][name=new_user_sel]').change(function () {
        let pi_cnf_text = $('#piConfirmWrapper');
        let pi_sel_text = $('#piSearchWrapper');
        if (this.value == 'not_pi') {
            pi_cnf_text.hide();
            pi_sel_text.show();
            $("#chk_pi").prop("required", false);
            $("#pi_search").prop("required", true);
        } else if (this.value == 'pi') {
            pi_cnf_text.show();
            pi_sel_text.hide();
            $("#chk_pi").prop("required", true);
            $("#pi_search").prop("required", false);
        }
    });

    $("input[type=text][name=pi]").keyup(function () {
        var searchWrapper = $("div.searchWrapper");
        $.ajax({
            url: "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/pi_search.php?search=" + $(this).val(),
            success: function (result) {
                searchWrapper.html(result);

                if (result == "") {
                    searchWrapper.hide();
                } else {
                    searchWrapper.show();
                }
            }
        });
    });

    $("div.searchWrapper").on("click", "span", function (event) {
        var textBox = $("input[type=text][name=pi]");
        textBox.val($(this).html());
    });

    /**
     * Hides the searchresult box on click anywhere
     */
    $(document).click(function () {
        $("div.searchWrapper").hide();
    });
</script>

<?php
require_once $LOC_FOOTER;
