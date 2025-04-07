<?php

namespace UnityWebPortal\lib;

require_once "../../resources/autoload.php";

require_once $LOC_HEADER;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $form_type = $SITE->array_get_or_bad_request("form_type", $_POST);
    switch ($form_type) {
        case "addKey":
            $added_keys = null;
            $add_type = $SITE->array_get_or_bad_request("add_type", $_POST);
            switch ($add_type) {
                case "paste":
                    $key = $SITE->array_get_or_bad_request("key", $_POST);
                    $added_keys = [$key];
                    break;
                case "import":
                    // FIXME the upload button should not work until an actual upload has been done
                    if ($_FILES["keyfile"]["tmp_name"] == "") {
                        $SITE->alert("No file uploaded.");
                        break;
                    }
                    $key = file_get_contents($_FILES["keyfile"]["tmp_name"]);
                    if ($key === FALSE){
                        $SITE->alert("File upload failed.");
                        break;
                    }
                    $added_keys = [$key];
                    break;
                case "generate":
                    $key = $SITE->array_get_or_bad_request("gen_key", $_POST);
                    $added_keys = [$key];
                    break;
                case "github":
                    try {
                        $gh_user = $SITE->array_get_or_bad_request("gh_user", $_POST);
                        $keys = $SITE->getGithubKeys($gh_user);
                        $added_keys = $keys;
                    } catch (GithubUserNotFoundOrNoKeysException $e) {
                        $SITE->alert("Github user not found, or Github user has no keys.");
                    }
                    break;
                default:
                    $SITE->bad_request("invalid add_type '$add_type'");
            }
            if (is_null($added_keys)) {
                break;
            }
            $all_are_valid = true;
            foreach ($added_keys as $key) {
                if (!$SITE->testValidSSHKey($key)) {
                    $all_are_valid = false;
                }
            }
            if (!$all_are_valid) {
                $SITE->alert("One or more of your SSH keys is invalid.");
                break;
            }
            // TODO when do I ignore cache and when do I not?
            $existing_keys = $USER->getSSHKeys(true);
            $totalKeys = array_merge($existing_keys, $added_keys);
            if(count($totalKeys) > $CONFIG["ldap"]["max_num_ssh_keys"]){
                $SITE->alert("Adding these SSH keys would exceed the maximum number allowed.");
                break;
            }
            $USER->setSSHKeys($totalKeys, $OPERATOR);
            break;
        case "delKey":
            $delIndex = $SITE->array_get_or_bad_request("delIndex", $_POST);
            if (!preg_match("/^[0-9]+$/", $delIndex)) {
                $SITE->bad_request("delIndex '$delIndex' is not digits");
                break;
            }
            $delIndex = (int)$delIndex;
            $keys = $USER->getSSHKeys();
            if (!(($delIndex >= 0) && ($delIndex < count($keys)))){
                $SITE->bad_request("delIndex $delIndex out of range");
                break;
            }
            unset($keys[$delIndex]);  // remove key from array
            $keys = array_values($keys);
            $USER->setSSHKeys($keys, $OPERATOR);  // Update user keys
            break;
        case "loginshell":
            $shell = $SITE->array_get_or_bad_request("shellSelect", $_POST);
            if ($shell == "custom") {
                $shell = $SITE->array_get_or_bad_request("shell", $_POST);
            }
            $USER->setLoginShell($shell, $OPERATOR);
            break;
        case "pi_request":
            if ($SQL->requestExists($USER->getUID())) {
                $SITE->bad_request("duplicate PI become request");
            }
            if ($USER->isPI()) {
                $SITE->bad_request("PI request to become PI");
            }
            if ($SQL->accDeletionRequestExists($USER->getUID())) {
                $SITE->bad_request("User requested account deletion also request to become PI");
            }
            $USER->getPIGroup()->requestGroup($SEND_PIMESG_TO_ADMINS);
            break;
        case "account_deletion_request":
            $hasGroups = count($USER->getPIGroups()) > 0;
            if ($hasGroups) {
                $SITE->bad_request("User still in PI groups but requested account deletion.");
            }
            if ($SQL->accDeletionRequestExists($USER->getUID())) {
                $SITE->bad_request("duplicate account deletion request");
            }
            $USER->requestAccountDeletion();
            break;
        default:
            $SITE->bad_request("invalid form_Type '$form_type'");
    }
}
?>

<h1>Account Settings</h1>
<hr>

<h5>Account Details</h5>

<p>
    <strong>Username</strong> <code><?php echo $USER->getUID(); ?></code>
    <br>
    <strong>Organization</strong> <code><?php echo $USER->getOrg(); ?></code>
    <br>
    <strong>Email</strong> <code><?php echo $USER->getMail(); ?></code>
</p>

<hr>

<h5>Account Status</h5>

<?php

$isActive = count($USER->getPIGroups()) > 0;
$isPI = $USER->isPI();

if ($isPI) {
    echo "<p>You are curently a <strong>principal investigator</strong> on the Unity Cluster</p>";
} elseif ($isActive) {
    echo "<p>You are curently a <strong>user</strong> on the Unity Cluster</p>";
} else {
    echo "<p>You are currently not assigned to any PI, and will be 
    <strong>unable to use the cluster</strong>. Go to the <a href='groups.php'>My PIs</a> 
    page to join a PI, or click on the button below if you are a PI</p>";
    echo "<p>Students should not request a PI account.</p>";
}

if (!$isPI) {
    if ($SQL->accDeletionRequestExists($USER->getUID())) {
        echo
        "<form action='' method='POST' id='piReq' 
        onsubmit='return confirm(\"Are you sure you want to request a PI account?\")'>
        <input type='hidden' name='form_type' value='pi_request'>";
        echo "<input type='submit' value='Request PI Account' disabled>";
        echo
        "<label style='margin-left: 10px'>
            You cannot request PI Account while you have requested account deletion.
        </label>";
        echo "</form>";
    } else {
        echo
        "<form action='' method='POST' id='piReq' 
        onsubmit='return confirm(\"Are you sure you want to request a PI account?\")'>
        <input type='hidden' name='form_type' value='pi_request'>";
        if ($SQL->requestExists($USER->getUID())) {
            echo "<input type='submit' value='Request PI Account' disabled>";
            echo "<label style='margin-left: 10px'>Your request has been submitted and is currently pending</label>";
        } else {
            echo "<input type='submit' value='Request PI Account'>";
        }
        echo "</form>";
    }
}
?>

<hr>

<h5>SSH Keys</h5>
<?php
$sshPubKeys = $USER->getSSHKeys();  // Get ssh public key attr

if (count($sshPubKeys) == 0) {
    echo "<p>You do not have any SSH public keys, press the button below to add one.</p>";
}

for ($i = 0; $sshPubKeys != null && $i < count($sshPubKeys); $i++) {  // loop through keys
    echo
    "<div class='key-box'>
    <textarea spellcheck='false' readonly>" . $sshPubKeys[$i] . "</textarea>
    <form action='' id='del-" . $i . "' 
    onsubmit='return confirm(\"Are you sure you want to delete this SSH key?\");' method='POST'>
    <input type='hidden' name='delIndex' value='$i'>
    <input type='hidden' name='form_type' value='delKey'>
    <input type='submit' value='&times;'>
    </form>
    </div>";
}

?>

<button type="button" class="plusBtn btnAddKey">&#43;</button>

<hr>

<h5>Login Shell</h5>
<form action="" method="POST">

    <input type="hidden" name="form_type" value="loginshell">

    <select id="loginSelector" name= "shellSelect"> 

        <option value="" disabled hidden>Select Login Shell...</option>

        <?php
        $cur_shell = $USER->getLoginShell();
        $found_selector = false;
        foreach ($CONFIG["loginshell"]["shell"] as $shell) {
            if ($cur_shell == $shell) {
                echo "<option selected>$shell</option>";
                $found_selector = true;
            } else {
                echo "<option>$shell</option>";
            }
        }

        if ($found_selector) {
            echo "<option value='custom'>Custom</option>";
        } else {
            echo "<option value='custom' selected>Custom</option>";
        }
        ?>
    </select>

    <?php

    if ($found_selector) {
        echo "<input id='customLoginBox' type='text' 
        placeholder='Enter login shell path (ie. /bin/bash)' name='shell'>";
    } else {
        echo "<input id='customLoginBox' type='text' 
        placeholder='Enter login shell path (ie. /bin/bash)' name='shell' value='$cur_shell'>";
    }

    ?>
    <br>
    <input type='submit' value='Set Login Shell'>

</form>

<hr>

<h5>Account Deletion</h5>
<?php
$hasGroups = count($USER->getPIGroups()) > 0;

if ($hasGroups) {
    echo "<p>You cannot request to delete your account while you are in a PI group.</p>";
} else {
    echo
    "<form action='' method='POST' id='accDel' 
    onsubmit='return confirm(\"Are you sure you want to request an account deletion?\")'>
    <input type='hidden' name='form_type' value='account_deletion_request'>";
    if ($SQL->accDeletionRequestExists($USER->getUID())) {
        echo "<input type='submit' value='Request Account Deletion' disabled>";
        echo "<label style='margin-left: 10px'>Your request has been submitted and is currently pending</label>";
    } else {
        echo "<input type='submit' value='Request Account Deletion'>";
    }
    echo "</form>";
}

?>

<hr>


<script>
    $("button.btnAddKey").click(function() {
        openModal("Add New Key", "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/new_key.php");
    });

    var customLoginBox = $("#customLoginBox");
    if (customLoginBox.val() == "") {
        // login box is empty, so we hide it by default
        // if the login box had a value, that means it would be a custom shell
        // and should not hide by default
        customLoginBox.hide();
    }

    $("#loginSelector").change(function() {
        var customBox = $("#customLoginBox");
        if($(this).val() == "custom") {
            customBox.show();
        } else {
            customBox.hide();
        }
    });

    if ($("#loginSelector").val() == "custom") {
        $("#customLoginBox").show();
    }
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
    }
</style>

<?php
require_once $LOC_FOOTER;
