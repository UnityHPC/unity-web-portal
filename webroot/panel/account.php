<?php

require_once "../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;

require_once $LOC_HEADER;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    switch ($_POST["form_type"]) {
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
                    $keys = UnitySite::getGithubKeys($gh_user);
                    $added_keys = $keys;
                    break;
            }

            if (!empty($added_keys)) {
                $added_keys = UnitySite::removeTrailingWhitespace($added_keys);
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
}

if (!$isPI) {
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

<?php
echo "<h5>Login Shell</h5>";
echo
"<div class='inline'>
<form action='' method='POST'>
<input type='hidden' name='form_type' value='loginshell'>
<select id=",">
  <option>/bin/bash</option>
  <option>/bin/zsh</option>
  <option>/bin/csh</option>
  <option>/bin/tcsh</option>
  <option>/bin/fish</option>
  <option>custom</option>
</select>
<br>

<input id="," placeholder="," style="," value='custom'
<value=". $USER->getLoginShell() . " required>
<input type='submit' value='Set Login Shell'>

</form>
</div>"
?>
<script>
$USER = $_POST['loginshell'];
echo("[".$USER."]");
</script>

<script>
document.querySelector("#choose").addEventListener('change', function() {
  var textarea = document.querySelector("#custom-text");
  if (this.value == 'custom') {
    textarea.style.display = 'none';
  } else {
    textarea.style.display = 'block';
  }

});
</script>

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
