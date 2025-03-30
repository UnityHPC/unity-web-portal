<?php

require_once "../../resources/autoload.php";

require_once $LOC_HEADER;

$invalid_ssh_dialogue = "<script type='text/javascript'>
alert('Invalid SSH key. Please verify your public key file is valid.');
</script>";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    switch ($_POST["form_type"]) {
        case "addKey":
            $added_keys = array();

            switch ($_POST["add_type"]) {
                case "paste":
                    // FIXME sanitize input
                    $key = $_POST["key"];
                    if ($SITE->testValidSSHKey($key)) {
                        array_push($added_keys, $key);
                    } else {
                        echo $invalid_ssh_dialogue;
                    }
                    break;
                case "import":
                    $keyfile = $_FILES["keyfile"]["tmp_name"];
                    // FIXME sanitize input
                    $key = file_get_contents($keyfile);
                    if ($SITE->testValidSSHKey($key)) {
                        array_push($added_keys, $key);
                    } else {
                        echo $invalid_ssh_dialogue;
                    }
                    break;
                case "generate":
                    // FIXME check valid key, sanitize input
                    array_push($added_keys, $_POST["gen_key"]);
                    break;
                case "github":
                    // FIXME check valid key, sanitize input
                    $gh_user = $_POST["gh_user"];
                    if (empty($gh_user)) {
                        break; // FIXME this should be an error
                    }
                    $keys = $SITE->getGithubKeys($gh_user);
                    foreach ($keys as $key) {
                        // FIXME this should be an error
                        if ($SITE->testValidSSHKey($key)) {
                            array_push($added_keys, $key);
                        }
                    }
                    break;
            }

            if (!empty($added_keys)) {
                $added_keys = $SITE->removeTrailingWhitespace($added_keys);
                $totalKeys = array_merge($USER->getSSHKeys(), $added_keys);
                $USER->setSSHKeys($totalKeys, $OPERATOR);
            }
            break;
        case "delKey":
            $keys = $USER->getSSHKeys();
            // FIXME check valid index
            unset($keys[intval($_POST["delIndex"])]);  // remove key from array
            $keys = array_values($keys);

            $USER->setSSHKeys($keys, $OPERATOR);  // Update user keys
            break;
        case "loginshell":
            // FIXME sanitize input
            if ($_POST["shellSelect"] == "custom") {
                $USER->setLoginShell($_POST["shell"], $OPERATOR);
            } else {
                $USER->setLoginShell($_POST["shellSelect"], $OPERATOR);
            }
            break;
        case "pi_request":
            if (!$USER->isPI()) {
                if (!$SQL->requestExists($USER->getUID())) {
                    $USER->getPIGroup()->requestGroup($SEND_PIMESG_TO_ADMINS);
                }
            }
            break;
        case "account_deletion_request":
            $hasGroups = count($USER->getGroups()) > 0;
            if ($hasGroups) {
                // FIXME make an error message?
                die();
                break;
            }
            if (!$SQL->accDeletionRequestExists($USER->getUID())) {
                $USER->requestAccountDeletion();
            }
            break;
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

$isActive = count($USER->getGroups()) > 0;
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
$hasGroups = count($USER->getGroups()) > 0;

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
