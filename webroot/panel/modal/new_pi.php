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
    let ownerInfo = null;
    const $input = $("input[name=pi]");
    const $wrapper = $("div.searchWrapper");
    const $submit = $("#newPIform-submit");

    $.ajax({
        url: '<?php echo getURL("panel/ajax/list_pi_groups_owner_info.php") ?>',
        success: data => ownerInfo = JSON.parse(data),
        error: result => console.error(result.responseText),
    });

    const search = (query) => {
        if (!ownerInfo || !query) return [];
        const lower = query.toLowerCase();
        return Object.entries(ownerInfo)
            .filter(([gid, { mail, gecos }]) =>
                gid.toLowerCase().includes(lower) ||
                gecos.toLowerCase().includes(lower) ||
                mail.toLowerCase().includes(lower)
            )
            .map(([gid]) => gid);
    };

    const updateSearch = () => {
        const query = $input.val();
        const results = search(query);
        if (results.length === 0) {
            $wrapper.html("").hide();
        } else {
            $wrapper.html(results.map(gid => `<span>${gid}</span>`).join('')).show();
        }
        $submit.prop("disabled", !results.includes(query));
    };

    $input.on("keyup", updateSearch);
    $wrapper.on("click", "span", function() {
        $input.val($(this).text());
        updateSearch();
    });
    $(document).on("click", () => $wrapper.hide());
</script>
