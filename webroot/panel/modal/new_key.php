<?php

require_once __DIR__ . "/../../../resources/autoload.php";  // Load required libs
use UnityWebPortal\lib\UnityHTTPD;
?>

<form
    id="newKeyform"
    enctype="multipart/form-data"
    method="POST"
    action="<?php echo getURL("panel/account.php"); ?>"
>
    <?php echo UnityHTTPD::getCSRFTokenHiddenFormInput(); ?>
    <input type='hidden' name='form_type' value='addKey'>

    <div class='inline'>
        <input type="radio" id="paste" name="add_type" value="paste" checked>
        <label for="paste">Paste Key</label>
    </div>

    <div class='inline'>
        <input type="radio" id="import" name="add_type" value="import">
        <label for="import">Local File</label>
    </div>

    <div class='inline'>
        <input type="radio" id="generate" name="add_type" value="generate">
        <label for="generate">Generate Key</label>
    </div>

    <div class='inline'>
        <input type="radio" id="github" name="add_type" value="github">
        <label for="github">Import from GitHub</label>
    </div>

    <hr>

    <div id="key_paste">
        <textarea placeholder="ssh-rsa AAARs1..." form="newKeyform" name="key"></textarea>
        <input type="submit" value="Add Key" id="add-key" disabled />
    </div>

    <div style="display: none;" id="key_import">
        <label for="keyfile">Select local file:</label>
        <input type="file" name="keyfile" />
        <input type="submit" value="Import Key" disabled />
    </div>

    <div style="display: none;" id="key_generate">
        <input type="hidden" name="gen_key" />
        <button type="button" class="btnLin">OpenSSH</button>
        <button type="button" class="btnWin">PuTTY</button>
    </div>

    <div style="display: none;" id="key_github">
        <div class='inline'>
            <input type="text" name="gh_user" placeholder="GitHub Username" />
            <input type="submit" value="Import Key(s)" disabled />
        </div>
    </div>
</form>

<script>
    $("input[type=radio]").change(function() {
        if ($(this).is(":checked")) {
            $("[id^=key_]").hide()  // Hide existing divs
            $("div#key_" + $(this).attr('id')).show();  // show only one div
        }
    });

    function generateKey(type) {
        var pubSection = "<section class='pubKey'>";
        var privSection = "<section class='privKey'>";
        var endingSection = "</section>";

        $.ajax({
            url: "<?php echo getURL("js/ajax/ssh_generate.php"); ?>?type=" + type,
            success: function(result) {
                var pubKey = result.substr(result.indexOf(pubSection) + pubSection.length,
                result.indexOf(endingSection) - result.indexOf(pubSection) - pubSection.length);
                var privKey = result.substr(result.indexOf(privSection) + privSection.length,
                result.indexOf(endingSection, result.indexOf(endingSection) + 1) -
                result.indexOf(privSection) - privSection.length);
                $("input[type=hidden][name=gen_key]").val(pubKey);
                downloadFile(privKey, "privkey." + type); // Force download of private key

                $("#newKeyform").submit();
            }
        });
    }

    $("div#key_generate > button").click(function() {
        // get type
        if ($(this).hasClass('btnWin')) {
            var type = "ppk";
        } else if ($(this).hasClass('btnLin')) {
            var type = "key";
        }

        setTimeout(() => generateKey(type), 300);
    });

    $("textarea[name=key]").on("input", function() {
        var key = $(this).val();
        $.ajax({
            url: "<?php echo getURL("js/ajax/ssh_validate.php"); ?>",
            type: "POST",
            data: {
                key: key
            },
            success: function(result) {
                const res = result.replace(key, "");
                if (res == "true") {
                    $("input[id=add-key]").prop("disabled", false);
                    $("textarea[name=key]").css("box-shadow", "none");
                } else {
                    $("input[id=add-key]").prop("disabled", true);
                    $("textarea[name=key]").css("box-shadow", "0 0 0 0.3rem rgba(220,53,69,0.25)");
                }
            }
        });
    });

    $("input[name=keyfile]").on("change", function() {
        $(this).siblings("input[type=submit]").prop("disabled", (this.files.length === 0));
    });
    $("input[name=gh_user]").on("input", function() {
        $(this).siblings("input[type=submit]").prop("disabled", (this.value === ""));
    });
</script>
