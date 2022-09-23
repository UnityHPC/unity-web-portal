<?php
require "../../resources/autoload.php";

require_once $LOC_HEADER;

function removeTrailingWhitespace($arr)
{
    $out = array();
    foreach ($arr as $str) {
        $new_string = rtrim($str);
        array_push($out, $new_string);
    }

    return $out;
}

function getGithubKeys($username)
{
    $url = "https://api.github.com/users/$username/keys";
    $headers = array(
        "User-Agent: Unity Cluster User Portal"
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $output = json_decode(curl_exec($curl), true);
    curl_close($curl);

    $out = array();
    foreach ($output as $value) {
        array_push($out, $value["key"]);
    }

    return $out;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    switch($_POST["form_type"]) {
        case "addKey":
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
            break;
        case "delKey":
            $keys = $USER->getSSHKeys();
            unset($keys[intval($_POST["delIndex"])]);  // remove key from array
            $keys = array_values($keys);

            $USER->setSSHKeys($keys);  // Update user keys
            break;
        case "loginshell":
            $USER->setLoginShell($_POST["loginshell"]);
            break;
        case "pi_request":
            if (!$USER->isPI()) {
                if (!$SQL->requestExists($USER->getUID())) {
                    $USER->getPIGroup()->requestGroup();
                }
            }
            break;
        case "delAccount":
            $USER->deleteUser();
            redirect($CONFIG["site"]["prefix"] . "/");
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
    echo "<p>You are currently not assigned to any PI, and will be <strong>unable to use the cluster</strong>. Go to the <a href='groups.php'>My PIs</a> page to join a PI, or click on the button below if you are a PI</p>";
}

if (!$isPI) {
    echo
    "<form action='' method='POST' id='piReq' onsubmit='return confirm(\"Are you sure you want to request a PI account? You must be a principal investigator at your organization to have a PI account.\");'>
    <input type='hidden' name='form_type' value='pi_request'>";
    if ($SQL->requestExists($USER->getUID())) {
        echo "<input type='submit' value='Request PI Account' disabled>";
        echo "<label>Your request has been submitted and is currently pending</label>";
    } else {
        echo "<input type='submit' value='Request PI Account'>";
    }
    echo "</form>";
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
    <form action='' id='del-" . $i . "' onsubmit='return confirm(\"Are you sure you want to delete this SSH key?\");' method='POST'>
    <input type='hidden' name='delIndex' value='$i'>
    <input type='hidden' name='form_type' value='delKey'>
    <input type='submit' value='&times;'>
    </form>
    </div>";
}
?>

<button type="button" class="plusBtn btnAddKey">&#43;</button>

<hr>

<?php
echo "<h5>Login Shell</h5>";
echo
"<div class='inline'>
<form action='' method='POST'>
<input type='hidden' name='form_type' value='loginshell'>
<input type='text' name='loginshell' placeholder='Login Shell (ie. /bin/bash)' value=" . $USER->getLoginShell() . " required>
<input type='submit' value='Set Login Shell'>
</form>
</div>";
?>

<hr>
<h5>Danger Zone</h5>

<?php
if ($USER->isPI()) {
    echo "<form action='javascript:void(0);'>";
    echo "<input type='submit' value='PI Group Exists - Cannot Delete Account' disabled>";
} else {
    echo "<form method='POST' action='' onsubmit='return confirm(\"Are you sure you want to delete your account? You will no longer be able to access Unity.\")'>";
    echo "<input type='hidden' name='form_type' value='delAccount'>";
    echo "<input type='submit' value='Delete My Account'>";
}
echo "</form>";
?>

<script>
    $("button.btnAddKey").click(function() {
        openModal("Add New Key", "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/new_key.php");
    });
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
?>