<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;

$hasGroups = count($USER->getPIGroupGIDs()) > 0;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    switch (UnitySite::getPostData("form_type")) {
        case "addKey":
            $keys = array();
            switch (UnitySite::getPostData("add_type")) {
                case "paste":
                    array_push($keys, UnitySite::getPostData("key"));
                    break;
                case "import":
                    $key = UnitySite::getUploadedFileContents("keyfile");
                    array_push($keys, $key);
                    break;
                case "generate":
                    array_push($keys, UnitySite::getPostData("gen_key"));
                    break;
                case "github":
                    $githubUsername = UnitySite::getPostData("gh_user");
                    $githubKeys = $GITHUB->getSshPublicKeys($githubUsername);
                    $keys = array_merge($keys, $githubKeys);
                    break;
            }
            if (!empty($keys)) {
                $keys = array_map("trim", $keys);
                $validKeys = array_filter(
                    $keys,
                    ["UnityWebPortal\lib\UnitySite", "testValidSSHKey"]
                );
                $USER->setSSHKeys(array_merge($USER->getSSHKeys(), $validKeys));
                if (count($keys) != count($validKeys)) {
                    UnitySite::alert("invalid SSH key");
                }
            }
            break;
        case "delKey":
            $keys = $USER->getSSHKeys();
            $indexStr = $_POST["delIndex"];
            if (!preg_match("/^[0-9]+$/", $indexStr)) {
                break;
            }
            $index = intval($indexStr);
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
                UnitySite::badRequest("already a PI");
            }
            if ($SQL->requestExists($USER->uid)) {
                UnitySite::badRequest("already requested to be PI");
            }
            if ($USER->uid != $SSO["user"]) {
                UnitySite::badRequest(
                    "cannot request due to uid mismatch: " .
                    "USER='{$USER->uid}' SSO[user]='{$SSO["user"]}'"
                );
            }
            $USER->getPIGroup()->requestGroup(
                $SSO["firstname"],
                $SSO["lastname"],
                $SSO["mail"],
                $SSO["org"],
                $SEND_PIMESG_TO_ADMINS
            );
            break;
        case "cancel_pi_request":
            $USER->getPIGroup()->cancelGroupRequest();
            break;
        case "account_deletion_request":
            if ($hasGroups) {
                break;
            }
            if (!$SQL->accDeletionRequestExists($USER->uid)) {
                $USER->requestAccountDeletion();
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
    <h5>AccountStatus</h5>
";

$isPI = $USER->isPI();

if ($isPI) {
    echo "<p>You are curently a <strong>principal investigator</strong> on the Unity Cluster</p>";
} elseif ($hasGroups) {
    echo "<p>You are curently a <strong>user</strong> on the Unity Cluster</p>";
} else {
    echo "
        <p>
            You are currently not assigned to any PI, and will be
            <strong>unable to use the cluster</strong>.
            Go to the
            <a href='groups.php'>My PIs</a>
            page to join a PI, or click on the button below if you are a PI.
        </p>
        <p>Students should not request a PI account.</p>
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
    if ($SQL->accDeletionRequestExists($USER->uid)) {
        echo "<input type='submit' value='Request PI Account' disabled />";
        echo "
            <label style='margin-left: 10px'>
                You cannot request PI Account while you have requested account deletion.
            </label>
        ";
    } else {
        if ($SQL->requestExists($USER->uid)) {
            $prompt = "onclick='return confirm(\"Are you sure you want to cancel this request?\")";
            echo "<input type='submit' value='Cancel PI Account Request' $prompt'/>";
            echo "
                <label style='margin-left: 10px'>
                    Your request has been submitted and is currently pending
                </label>
               <input type='hidden' name='form_type' value='cancel_pi_request'/>
            ";
        } else {
            echo "<input type='hidden' name='form_type' value='pi_request'/>";
            $prompt = "onclick='return confirm(\"Are you sure you want to request a PI account?\")";
            echo "<input type='submit' value='Request PI Account' $prompt'/>";
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
    echo
    "<div class='key-box'>
        <textarea spellcheck='false' readonly>" . $sshPubKeys[$i] . "</textarea>
        <form
            action='' id='del-" . $i . "'
            onsubmit='return confirm(\"Are you sure you want to delete this SSH key?\");'
            method='POST'
        >
            <input type='hidden' name='delIndex' value='$i' />
            <input type='hidden' name='form_type' value='delKey' />
            <input type='submit' value='&times;' />
        </form>
    </div>";
}

echo '
    <button type="button" class="plusBtn btnAddKey"><span>&#43;</span></button>
    <hr>
    <h5>Login Shell</h5>
    <form action="" method="POST">
    <input type="hidden" name="form_type" value="loginshell" />
    <select id="loginSelector" class="code" name="shellSelect">
';
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
    echo "
        <form
            action=''
            method='POST'
            id='accDel'
            onsubmit='return confirm(\"Are you sure you want to request an account deletion?\")'
        >
        <input type='hidden' name='form_type' value='account_deletion_request' />
    ";
    if ($SQL->accDeletionRequestExists($USER->uid)) {
        echo "<input type='submit' value='Request Account Deletion' disabled />";
        echo "
            <label style='margin-left: 10px'>
            Your request has been submitted and is currently pending</label>
        ";
    } else {
        echo "<input type='submit' value='Request Account Deletion' />";
    }
    echo "</form>";
}

?>

<script>
    const sitePrefix = '<?php echo CONFIG["site"]["prefix"]; ?>';
    const ldapLoginShell = '<?php echo $USER->getLoginShell(); ?>';

    $("button.btnAddKey").click(function() {
        openModal("Add New Key", `${sitePrefix}/panel/modal/new_key.php`);
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
