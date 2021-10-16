<?php
require "../../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    if (isset($_POST["add_type"])) {
        //form was submitted
        $added_keys = array();

        switch ($_POST["add_type"]) {
            case "paste":
                array_push($added_keys, $_POST["key"]);
                break;
            case "import":
                array_push($added_keys, file_get_contents($_FILES['keyfile']['tmp_name']));
                break;
            case "generate":
                array_push($added_keys, $_POST["gen_key"]);
                break;
            case "github":
                $gh_user = $_POST["gh_user"];
                $keys = getGithubKeys($gh_user);
                $added_keys = $keys;
                break;
        }

        if (!empty($added_keys)) {
            $added_keys = removeTrailingWhitespace($added_keys);
            $totalKeys = array_merge($USER->getSSHKeys(), $added_keys);
            $USER->setSSHKeys($totalKeys);
        }
    } elseif (isset($_POST["delIndex"])) {
        $keys = $USER->getSSHKeys();
        unset($keys[intval($_POST["delIndex"])]);  // remove key from array
        $keys = array_values($keys);

        $USER->setSSHKeys($keys);  // Update user keys
    } elseif (isset($_POST["loginshell"])) {
        $USER->setLoginShell($_POST["loginshell"]);

        $message = "Login shell updated to " . $USER->getLoginShell() . ".";
    } elseif (isset($_POST["pi_request"])) {
        if (!$USER->isPI()) {
            if (!$SERVICE->sql()->requestExists($USER->getUID())) {
                $SERVICE->sql()->addRequest($USER->getUID());

                // Send approval email to admins
                $SERVICE->mail()->send("new_pi_request", array("netid" => $USER->getUID(), "firstname" => $USER->getFirstname(), "lastname" => $USER->getLastname(), "mail" => $USER->getMail()));
                    
                $message = "A request for a PI account has been sent to admins for review";
            }
        }
    }
}
?>

<h1><?php echo unity_locale::ACCOUNT_HEADER_MAIN; ?></h1>

<label>Account Status</label>

<?php

$isActive = count($USER->getGroups()) > 0;
$isPI = $USER->isPI();

if ($isPI) {
    echo "<p>You are curently a <strong>principal investigator</strong> on the Unity Cluster</p>";
} elseif ($isActive) {
    echo "<p>You are curently a <strong>user</strong> on the Unity Cluster</p>";
} else {
    echo "<p>You are currently not assigned to any PI, and will be <strong>unable to use the cluster</strong>. Go to the <a href='groups.php'>My PIs</a> page to join a PI, or click on the button below if you are a PI</p>";
}

if (!$isPI) {
    echo "<form action='' method='POST' id='piReq'><input type='hidden' name='pi_request' value='yes'></form>";
    if ($SERVICE->sql()->requestExists($USER->getUID())) {
        echo "<button class='btnReqPI' disabled>Request PI Account</button>";
        echo "<span>Your request has been submitted and is currently pending</span>";
    } else {
        echo "<button class='btnReqPI'>Request PI Account</button>";
    }
}
?>

<hr>

<label>SSH Keys</label>
<?php
$sshPubKeys = $USER->getSSHKeys();  // Get ssh public key attr
for ($i = 0; $sshPubKeys != null && $i < count($sshPubKeys); $i++) {  // loop through keys
    echo "<div class='key-box'><textarea spellcheck='false' readonly>" . $sshPubKeys[$i] . "</textarea><form action='' method='POST'><input type='submit' class='btnRemove' value='&times;'><input type='hidden' name='delIndex' value='$i'></form></div>";
}
?>

<button type="button" class="plusBtn btnAddKey">&#43;</button>

<hr>

<?php
echo "<label>Login Shell</label><br>";
echo "<div class='inline'><form action='' method='POST'><input type='text' name='loginshell' placeholder='Login Shell (ie. /bin/bash)' value=" . $USER->getLoginShell() . "><input type='submit' value='Set Login Shell'></form></div>";
?>

<div>
    <?php
    if (isset($errors) && empty($errors)) {
        echo "<div class='checkmark'>&check;</div>";
    }
    ?>
</div>

<?php
// GYPSUM GOES HERE
//echo "<label>Cluster Access</label><br>";
//echo "";
?>

<script>
    $("button.btnAddKey").click(function() {
        openModal("Add New Key", "<?php echo config::PREFIX; ?>/panel/modal/new_key.php");
    });

    <?php
    if (isset($message)) {
        echo "messageModal('$message');";
    }
    ?>

    $("button.btnReqPI").click(function() {
        confirmModal("Are you sure you want to request a PI account? <strong>You need to be a PI to be approved</strong>", "#piReq");
    });
</script>

<style>
    .key-box {
        position: relative;
        width: auto;
        height: auto;
    }

    .key-box input[type=submit] {
        position: absolute;
        left: 0;
        bottom: 0;
        padding: 0;
        margin: 0;
    }

    .key-box textarea {
        word-wrap: break-word;
        word-break: break-all;
    }

    button.plusBtn {
        max-width: 612px;
    }

    div.modalContent {
        max-width: 600px;
    }
</style>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>