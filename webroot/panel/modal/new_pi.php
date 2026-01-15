<?php

require_once __DIR__ . "/../../../resources/autoload.php";
use UnityWebPortal\lib\UnityHTTPD;
$CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();
?>

<form
    id="newPIform"
    method="POST"
    action="<?php echo getURL("panel/groups.php"); ?>"
>
    <?php echo $CSRFTokenHiddenFormInput; ?>
    <input type="hidden" name="form_type" value="addPIform">
    <div style="position: relative;">
        <input type="text" id="pi_search" name="pi" placeholder="Search PI by NetID" required>
        <div class="searchWrapper" style="display: none;"></div>
    </div>
    <label>
        <input type='checkbox' name='tos' value='agree' required>
        I have read and accept the
        <a href='<?php echo CONFIG["site"]["terms_of_service_url"]; ?>' target='_blank'>
            Terms of Service
        </a>.
    </label>
    <input id="newPIform-submit" type="submit" value="Send Request" disabled>
</form>

<script>
    var gid_to_owner_info = null;
    $.ajax({
        url: '<?php echo getURL("panel/ajax/list_pi_groups_owner_info.php") ?>',
        success: function(result) {
            gid_to_owner_info = JSON.parse(result);
        },
        error: function (result) {
            console.log(result.responseText);
        },
    });
    function search_pi_groups(x) {
        x = x.toLowerCase()
        if (gid_to_owner_info == null) {
            console.log("gid_to_owner_info is null, returning empty search results...");
            return [];
        }
        if (x === "") {
            return [];
        }
        var output = [];
        for (const [gid, owner_attributes] of Object.entries(gid_to_owner_info)) {
            const mail = owner_attributes["mail"];
            const gecos = owner_attributes["gecos"];
            if (gid.toLowerCase().includes(x) || gecos.includes(x) || mail.includes(x)) {
                output.push(gid);
            }
        }
        return output;
    }
    var search_box = $("input[type=text][name=pi]");
    function update_search() {
        const search = search_box.val();
        var searchWrapper = $("div.searchWrapper");
        const gids = search_pi_groups(search);
        if (gids.length === 0) {
            searchWrapper.html("");
            searchWrapper.hide();
        } else {
            const html = gids.map(x => `<span>${x}</span>`).join('');
            searchWrapper.html(html);
            searchWrapper.show();
        }
        const is_match = gids.includes(search);
        $("#newPIform-submit").prop("disabled", !is_match);
    }

    $("input[type=text][name=pi]").keyup(function() {
        update_search();
    });
    $("div.searchWrapper").on("click", "span", function (event) {
        search_box.val($(this).html());
        update_search();
    });
    /**
     * Hides the searchresult box on click anywhere
     */
    $(document).click(function() {
        $("div.searchWrapper").hide();
    });
</script>
