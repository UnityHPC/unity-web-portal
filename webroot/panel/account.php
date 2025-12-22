<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UserFlag;
use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\exceptions\EncodingUnknownException;
use UnityWebPortal\lib\exceptions\EncodingConversionException;
use UnityWebPortal\lib\UnitySQL;

$hasGroups = count($USER->getPIGroupGIDs()) > 0;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    UnityHTTPD::validatePostCSRFToken();
    switch (UnityHTTPD::getPostData("form_type")) {
        case "addKey":
            $keys = array();
            switch (UnityHTTPD::getPostData("add_type")) {
                case "paste":
                    array_push($keys, UnityHTTPD::getPostData("key"));
                    break;
                case "import":
                    try {
                        $key = UnityHTTPD::getUploadedFileContents("keyfile");
                    } catch (EncodingUnknownException | EncodingConversionException $e) {
                        UnityHTTPD::badRequest("uploaded key has bad encoding", error: $e);
                    }
                    array_push($keys, $key);
                    break;
                case "generate":
                    array_push($keys, UnityHTTPD::getPostData("gen_key"));
                    break;
                case "github":
                    $githubUsername = UnityHTTPD::getPostData("gh_user");
                    $githubKeys = $GITHUB->getSshPublicKeys($githubUsername);
                    $keys = array_merge($keys, $githubKeys);
                    break;
            }
            if (!empty($keys)) {
                $keys = array_map("trim", $keys);
                $validKeys = array_filter($keys, "testValidSSHKey");
                $USER->setSSHKeys(array_merge($USER->getSSHKeys(), $validKeys));
                if (count($keys) != count($validKeys)) {
                    UnityHTTPD::badRequest(
                        "one more more invalid SSH keys were not added",
                        data: [
                            "keys_valid_added" => $validKeys,
                            "keys_invalid_not_added" => array_diff($keys, $validKeys),
                        ],
                    );
                }
            }
            break;
        case "delKey":
            $keys = $USER->getSSHKeys();
            $index = digits2int(UnityHTTPD::getPostData("delIndex"));
            if ($index >= count($keys)) {
                break;
            }
            unset($keys[$index]);
            $keys = array_values($keys);

            $USER->setSSHKeys($keys, $OPERATOR);
            break;
        case "loginshell":
            $USER->setLoginShell($_POST["shellSelect"], $OPERATOR);
            break;
        case "pi_request":
            if ($USER->isPI()) {
                UnityHTTPD::badRequest("already a PI");
            }
            if ($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI)) {
                UnityHTTPD::badRequest("already requested to be PI");
            }
            if ($_POST["tos"] != "agree") {
                UnityHTTPD::badRequest("user did not agree to terms of service");
            }
            $USER->getPIGroup()->requestGroup($SEND_PIMESG_TO_ADMINS);
            break;
        case "cancel_pi_request":
            $USER->getPIGroup()->cancelGroupRequest();
            break;
        case "account_deletion_request":
            if ($hasGroups) {
                break;
            }
            // FIXME send an error message if already exists
            if (!$SQL->accDeletionRequestExists($USER->uid)) {
                $USER->requestAccountDeletion();
            }
            break;
        case "cancel_account_deletion_request":
            // FIXME send an error message if doesn't exist
            if ($SQL->accDeletionRequestExists($USER->uid)) {
                $USER->cancelRequestAccountDeletion();
            }
            break;
    }
}

require $LOC_HEADER;

$uid = $USER->uid;
$org = $USER->getOrg();
$mail = $USER->getMail();
echo "
    <h1>Account Settings</h1>
    <hr>
    <h5>Account Details</h5>
    <table>
        <tr>
            <th>Username</th>
            <td><code>$uid</code></td>
        </tr>
        <tr>
            <th>Organization</th>
            <td><code>$org</code></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><code>$mail</code></td>
        </tr>
    </table>
    <hr>
    <h5>Account Status</h5>
";

$isPI = $USER->isPI();

if ($isPI) {
    echo "
        <p>You are curently a <strong>principal investigator</strong> on the UnityHPC Platform</p>
    ";
} elseif ($USER->getFlag(UserFlag::QUALIFIED)) {
    echo "<p>You are curently a <strong>qualified user</strong> on the UnityHPC Platform</p>";
} else {
    $tos_url = CONFIG["site"]["terms_of_service_url"];
    $form_url = getURL("panel/groups.php");
    echo "
        <p>
            You are currently an <strong>unqualified user</strong>, and will be
            <strong>unable to access UnityHPC Platform services</strong>.
            To become qualified, request to join a PI group, or if you are a PI, request a PI group.
            Do not request a PI group if you are a student.
        </p>
        <br>
        <form action='$form_url' method='GET'>
            <label>
                <input type='checkbox' name='tos' value='agree' required />
                I have read and accept the
                <a target='_blank' href='$tos_url'>Terms of Service</a>.
            </label>
            <br>
            <input type='submit' value='Request to Join a PI Group' />
        </form>
        <br>
    ";
}

if (!$isPI) {
    echo "
        <form
            action=''
            method='POST'
            id='piReq'
        >
    ";
    echo UnityHTTPD::getCSRFTokenHiddenFormInput();
    if ($SQL->accDeletionRequestExists($USER->uid)) {
        echo "<input type='submit' value='Request PI Account' disabled />";
        echo "
            <label style='margin-left: 10px'>
                You cannot request PI Account while you have requested account deletion.
            </label>
        ";
    } else {
        if ($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI)) {
            $onclick = "return confirm(\"Are you sure you want to cancel this request?\")";
            echo "<input type='submit' value='Cancel PI Account Request' onclick='$onclick'/>";
            echo "
                <label style='margin-left: 10px'>
                    Your request has been submitted and is currently pending
                </label>
               <input type='hidden' name='form_type' value='cancel_pi_request'/>
            ";
        } else {
            $onclick = "return confirm(\"Are you sure you want to request a PI account?\")";
            $tos_url = CONFIG["site"]["terms_of_service_url"];
            $account_policy_url = CONFIG["site"]["account_policy_url"];
            echo "
                <label>
                    <input type='checkbox' name='confirm_pi' value='agree' required>
                    I have read the PI
                    <a target='_blank' href='$account_policy_url'> account policy</a> guidelines.
                </label>
                <br>
                <label><input type='checkbox' name='tos' value='agree' required>
                    I have read and accept the
                    <a target='_blank' href='$tos_url'>Terms of Service</a>.
                </label>
                <br>
                <input type='hidden' name='form_type' value='pi_request'/>
                <input type='submit' value='Request a PI Group' onclick='$onclick'/>
            ";
        }
    }
    echo "</form>";
}
echo "<hr><h5>SSH Keys</h5>";

$sshPubKeys = $USER->getSSHKeys();

if (count($sshPubKeys) == 0) {
    echo "<p>You do not have any SSH public keys, press the button below to add one.</p>";
}

for ($i = 0; $sshPubKeys != null && $i < count($sshPubKeys); $i++) {
    $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
    echo
    "<div class='key-box'>
        <textarea spellcheck='false' readonly>" . $sshPubKeys[$i] . "</textarea>
        <form
            action='' id='del-" . $i . "'
            onsubmit='return confirm(\"Are you sure you want to delete this SSH key?\");'
            method='POST'
        >
            $CSRFTokenHiddenFormInput
            <input type='hidden' name='delIndex' value='$i' />
            <input type='hidden' name='form_type' value='delKey' />
            <input type='submit' value='&times;' />
        </form>
    </div>";
}

$CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
echo "
    <button type='button' class='plusBtn btnAddKey'><span>&#43;</span></button>
    <hr>
    <h5>Login Shell</h5>
    <form action='' method='POST'>
      $CSRFTokenHiddenFormInput
      <input type='hidden' name='form_type' value='loginshell' />
      <select id='loginSelector' class='code' name='shellSelect'>
";
foreach (CONFIG["loginshell"]["shell"] as $shell) {
    echo "<option>$shell</option>";
}
echo "
      </select>
      <br>
      <input id='submitLoginShell' type='submit' value='Set Login Shell' />
    </form>
    <hr>
    <h5>Account Deletion</h5>
";

if ($hasGroups) {
    echo "<p>You cannot request to delete your account while you are in a PI group.</p>";
} else {
    $CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
    if ($SQL->accDeletionRequestExists($USER->uid)) {
        echo "
            <p>Your request has been submitted and is currently pending.</p>
            <form
                action=''
                method='POST'
                onsubmit='
                    return confirm(
                        \"Are you sure you want to cancel your request for account deletion?\"
                    )
                '
            >
                $CSRFTokenHiddenFormInput
                <input type='hidden' name='form_type' value='cancel_account_deletion_request' />
                <input type='submit' value='Cancel Account Deletion Request' />
            </form>
        ";
    } else {
        echo "
            <form
                action=''
                method='POST'
                onsubmit='return confirm(\"Are you sure you want to request an account deletion?\")'
            >
                $CSRFTokenHiddenFormInput
                <input type='hidden' name='form_type' value='account_deletion_request' />
                <input type='submit' value='Request Account Deletion' />
            </form>
        ";
    }
}

?>

<script>
    const url = '<?php echo getURL("panel/modal/new_key.php")?>';
    const ldapLoginShell = '<?php echo $USER->getLoginShell(); ?>';

    $("button.btnAddKey").click(function() {
        openModal("Add New Key", url);
    });

    $("#loginSelector option").each(function(i, e) {
        if ($(this).val() == ldapLoginShell) {
            $(this).prop("selected", true);
        }
    });

    function enableOrDisableSubmitLoginShell() {
        if ($("#loginSelector").val() == ldapLoginShell) {
            $("#submitLoginShell").prop("disabled", true);
        } else {
            $("#submitLoginShell").prop("disabled", false);
        }
    }
    $("#loginSelector").change(enableOrDisableSubmitLoginShell);
    enableOrDisableSubmitLoginShell()
</script>

<style>
    .key-box {
        position: relative;
        width: auto;
        height: auto;
        max-width: 700px;
    }

    .key-box input[type=submit] {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        padding: 5px;
        width: 32px;
        border-radius: 0 3px 3px 0;
        font-size: 20pt;
        margin: 0;
    }

    .key-box textarea {
        word-wrap: break-word;
        word-break: break-all;
        width: calc(100% - 44px);
        border-radius: 3px 0 0 3px;
        font-family: monospace;
    }
</style>

<?php
require_once $LOC_FOOTER;
