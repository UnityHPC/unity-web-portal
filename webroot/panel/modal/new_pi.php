<?php

require_once __DIR__ . "/../../../resources/autoload.php";
use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnityGroup;
$CSRFTokenHiddenFormInput = UnityHTTPD::getCSRFTokenHiddenFormInput();

// cache PI group info in $_SESSION for ajax pi_search.php
// cache persists only until the user loads this page again
$owner_uids = $LDAP->getAllPIGroupOwnerUIDs();
$owner_attributes = $LDAP->getUsersAttributes(
    $owner_uids,
    ["uid", "gecos", "mail"],
    default_values: ["gecos" => [""], "mail" => [""]]
);
$pi_group_gid_to_owner_gecos_and_mail = [];
foreach ($owner_attributes as $attributes) {
    $gid = UnityGroup::ownerUID2GID($attributes["uid"][0]);
    $pi_group_gid_to_owner_gecos_and_mail[$gid] = [$attributes["gecos"][0], $attributes["mail"][0]];
}
$_SESSION["pi_group_gid_to_owner_gecos_and_mail"] = $pi_group_gid_to_owner_gecos_and_mail;
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
    <input type="submit" value="Send Request" id="newPIform-submit" disabled>
</form>

<script>
    (function () {
        const input = $("input[name=pi]");
        const wrapper = $("div.searchWrapper");
        const submit = $("#newPIform-submit");

        const updateSearch = () => {
            const query = input.val();
            $.ajax({
                url: '<?php echo getURL("panel/ajax/pi_search.php") ?>',
                data: {"search": query},
                success: function(data) {
                    results = JSON.parse(data);
                    if (results.length === 0) {
                        wrapper.html("").hide();
                        submit.prop("disabled", true);
                    } else if (results.includes(query)) {
                        wrapper.html("").hide();
                        submit.prop("disabled", false);
                    } else {
                        submit.prop("disabled", true);
                        const html = results.map(gid => `<span>${gid}</span>`).join('');
                        wrapper.html(html).show();
                    }
                },
                error: result => console.error(result.responseText),
            });
        };

        input.on("keyup", () => updateSearch());
        wrapper.on("click", "span", function() {
            input.val($(this).text());
            updateSearch();
        });
        $(document).on("click", () => wrapper.hide());
    })();
</script>
