<?php
require "../../resources/autoload.php";

require_once LOC_HEADER;

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
            if (!$SQL->requestExists($USER->getUID())) {
                $USER->getPIGroup()->requestGroup();

                $message = "A request for a PI account has been sent to admins for review";
            }
        }
    }
}
?>

<h1><?php echo unity_locale::ACCOUNT_HEADER_MAIN; ?></h1>

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
    echo "<form action='' method='POST' id='piReq'><input type='hidden' name='pi_request' value='yes'></form>";
    if ($SQL->requestExists($USER->getUID())) {
        echo "<button class='btnReqPI' disabled>Request PI Account</button>";
        echo "<label>Your request has been submitted and is currently pending</label>";
    } else {
        echo "<button class='btnReqPI'>Request PI Account</button>";
    }
}
?>

<hr>

<h5>SSH Keys</h5>
<?php
$sshPubKeys = $USER->getSSHKeys();  // Get ssh public key attr
for ($i = 0; $sshPubKeys != null && $i < count($sshPubKeys); $i++) {  // loop through keys
    echo "<div class='key-box'><textarea spellcheck='false' readonly>" . $sshPubKeys[$i] . "</textarea><button class='btnRemove' data-id='" . $i . "'>&times;</button><form action='' id='del-" . $i . "' method='POST'><input type='hidden' name='delIndex' value='$i'></form></div>";
}
?>

<button type="button" class="plusBtn btnAddKey">&#43;</button>

<hr>

<?php
echo "<h5>Login Shell</h5>";
echo "<div class='inline'><form action='' method='POST'><input type='text' name='loginshell' placeholder='Login Shell (ie. /bin/bash)' value=" . $USER->getLoginShell() . "><input type='submit' value='Set Login Shell'></form></div>";
?>

<?php
// GYPSUM GOES HERE
//echo "<label>Cluster Access</label><br>";
//echo "";
?>

<script>
    $("button.btnAddKey").click(function() {
        openModal("Add New Key", "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/new_key.php");
    });

    <?php
    if (isset($message)) {
        echo "messageModal('$message');";
    }
    ?>

    $("button.btnReqPI").click(function() {
        confirmModal("Are you sure you want to request a PI account? <strong>You need to be a PI to be approved</strong>", "#piReq");
    });

    $("button.btnRemove").click(function() {
        var id = $(this).attr("data-id");
        confirmModal("Are you sure you want to delete this SSH key?", "#del-" + id);
    });
</script>

<style>
    .key-box {
        position: relative;
        width: auto;
        height: auto;
        max-width: 700px;
    }

    .key-box button {
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

    button.plusBtn {
        max-width: 700px;
    }
</style>

<?php
require_once LOC_FOOTER;
?>