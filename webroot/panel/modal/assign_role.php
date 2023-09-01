<?php

require_once "../../../resources/autoload.php";  // Load required libs
?>

<form id="assignRoleForm" method="POST" action="<?php echo $CONFIG["site"]["prefix"]; ?>/panel/view_group.php?group=<?php echo $_GET["group"]; ?>">
    <input type="hidden" name="form_name" value="assignRoleForm">
    <div style="position: relative;">
        <input type="text" id="member_search" name="operated_on_uid" placeholder="Search Members by UID" required>
        <div class="searchWrapper" style="display: none;"></div>
    </div>
    <input type="submit" value="Assign">
</form>

<script>
    $("input[type=text][name=operated_on_uid]").keyup(function() {
        var searchWrapper = $("div.searchWrapper");
        $.ajax({
            url: "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/member_search.php?search=" +
             $(this).val() + "&group=<?php echo $_GET["group"]; ?>",
            success: function(result) {
                searchWrapper.html(result);

                if (result == "") {
                    searchWrapper.hide();
                } else {
                    searchWrapper.show();
                }
            }
        });
    });

    $("div.searchWrapper").on("click", "span", function (event) {
        var textBox = $("input[type=text][name=operated_on_uid]");
        textBox.val($(this).html());
    });

    /**
     * Hides the searchresult box on click anywhere
     */
    $(document).click(function() {
        $("div.searchWrapper").hide();
    });
</script>