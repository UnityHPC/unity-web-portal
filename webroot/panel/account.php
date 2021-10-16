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
    }
}
?>

<h1><?php echo unity_locale::ACCOUNT_HEADER_MAIN; ?></h1>

<div class="pageTop">
    <p>Any changes made on this page may take a few minutes to take effect on Unity.</p>
    <pre>ssh -i [downloaded key] <?php echo $USER->getUID(); ?>@unity.rc.umass.edu</pre>
</div>

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