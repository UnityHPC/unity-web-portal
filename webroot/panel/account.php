<?php
require "../../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $errors = array();

    //form was submitted
    $keys = array();
    for ($i = 0; isset($_POST["key" . $i]); $i++) {
        $key = $_POST["key" . $i];
        if (!empty($key)) {
            array_push($keys, $key);  // Push Each SSH Box
        }
    }

    $user->setSSHKeys($keys);

    if (isset($_POST["loginshell"])) {
        $user->setLoginShell($_POST["loginshell"]);
    }
}
?>

<h1><?php echo unity_locale::ACCOUNT_HEADER_MAIN; ?></h1>

<div class="pageTop">
    <span>We use SSH keys for console authentication to Unity. Users are not given passwords for this reason.</span>
    <pre>ssh -i [downloaded key] <?php echo $user->getUID(); ?>@unity.rc.umass.edu</pre>
</div>

<div class="pageControls">

    <button type="button" class="btnAddKey"><?php echo unity_locale::ACCOUNT_LABEL_NEW; ?></button>

    <div style="display: inline-block;" class="btnDropdown">
        <button style="width: 170px" type="button"><?php echo unity_locale::ACCOUNT_LABEL_GENERATE; ?></button>
        <div>
            <button type="button" class="btnWin"><?php echo unity_locale::ACCOUNT_LABEL_GENWIN; ?></button>
            <button type="button" class="btnLin"><?php echo unity_locale::ACCOUNT_LABEL_GENLIN; ?></button>
        </div>
    </div>

</div>

<form id="sshForm" action="" method="POST">
    <label>SSH Keys</label>
    <?php
    $sshPubKeys = $user->getSSHKeys();  // Get ssh public key attr
    for ($i = 0; $sshPubKeys != null && $i < count($sshPubKeys); $i++) {  // loop through keys
        echo "<div class='key-box'><textarea placeholder='" . unity_locale::ACCOUNT_LABEL_KEY . "' spellcheck='false' form='sshForm'>" . $sshPubKeys[$i] . "</textarea><button type='button' class='btnRemove' aria-label='" . unity_locale::ACCOUNT_LABEL_REMKEY . "' onclick='$(this).parent().remove()'></button></div>";
    }
    ?>

    <hr>

    <?php
    // only allow changing login shell if user is active
    if ($user->isActive()) {
        echo "<label>Login Shell</label>";
        echo "<input type='text' name='loginshell' placeholder='Login Shell (ie. /bin/bash)' value=" . $user->getLoginShell() . ">";
        echo "<hr>";
    }
    ?>

    <input type="submit" value="<?php echo unity_locale::LABEL_APPLY; ?>">
</form>

<?php
printMessages($errors, unity_locale::MES_APPLY);
?>

<script>
    var startingText = $("#sshForm").text();

    $("div.btnDropdown > div > button").click(function() {
        // Set type GET parameter
        if ($(this).hasClass("btnWin")) {
            var type = "ppk";
        } else if ($(this).hasClass("btnLin")) {
            var type = "key";
        }

        var pubSection = "<section class='pubKey'>";
        var privSection = "<section class='privKey'>";
        var endingSection = "</section>";

        $.ajax({
            url: "/js/ajax/ssh_generate.php?type=" + type,
            success: function(result) {
                var pubKey = result.substr(result.indexOf(pubSection) + pubSection.length, result.indexOf(endingSection) - result.indexOf(pubSection) - pubSection.length);
                var privKey = result.substr(result.indexOf(privSection) + privSection.length, result.indexOf(endingSection, result.indexOf(endingSection) + 1) - result.indexOf(privSection) - privSection.length);
                $("button.btnAddKey").trigger("click"); // Add new text box
                $("#sshForm > div").last().children("textarea").text(pubKey); // Populate new box with public key
                downloadFile(privKey, "privkey." + type); // Force download of private key
            }
        });
    });

    function downloadFile(text, filename) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);

        element.style.display = "none";
        $("body").append(element);
        element.click();
        element.remove();
    }

    $("button.btnAddKey").click(function() {
        var newBoxString = "<div class='key-box'><textarea placeholder='<?php echo unity_locale::ACCOUNT_LABEL_KEY; ?>' spellcheck='false' form='sshForm'></textarea><button type='button' class='btnRemove' aria-label='<?php echo unity_locale::ACCOUNT_LABEL_REMKEY; ?>' onclick='$(this).parent().remove()'></button></div>";

        var boxes = $("#sshForm > div");
        if (boxes.length) {
            boxes.last().after(newBoxString);
        } else {
            $("#sshForm").prepend(newBoxString);
        }
    });

    var submitLock = false;
    $("#sshForm").submit(function() { // Assign step values to textboxes so php can read them in the post request later
        submitLock = true;
        var step = 0;
        $(this).children("div").each(function() {
            $(this).children("textarea").attr("name", "key" + step);
            step++;
        });
    });

    $(window).on("beforeunload", function() {
        if (!submitLock && $("#sshForm").text() != startingText) {
            return "You haven't saved your changes. Are you sure?"; // This message won't actually show so its not included in locale
        }
    });
</script>

<style>
    .key-box {
        position: relative;
        width: auto;
        height: auto;
    }

    .key-box button {
        position: absolute;
        left: 0;
        bottom: 0;

    }

    .key-box textarea {
        word-wrap: break-word;
        word-break: break-all;
    }
</style>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>