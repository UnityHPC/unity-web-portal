<?php

require_once __DIR__ . "/../../../resources/autoload.php";  // Load required libs
?>

<form id="newPIform" method="POST" action="<?php echo $CONFIG["site"]["prefix"]; ?>/panel/groups.php">
    <input type="hidden" name="form_type" value="addPIform">
    <div style="position: relative;">
        <input type="text" id="pi_search" name="pi" placeholder="Search PI by NetID" required>
        <div class="searchWrapper" style="display: none;"></div>
    </div>
    <input type="submit" value="Send Request">
</form>

<script>
    $("input[type=text][name=pi]").keyup(function() {
        var searchWrapper = $("div.searchWrapper");
        $.ajax({
            url: "<?php echo $CONFIG["site"]["prefix"]; ?>/panel/modal/pi_search.php?search=" + $(this).val(),
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
        var textBox = $("input[type=text][name=pi]");
        textBox.val($(this).html());
    });

    /**
     * Hides the searchresult box on click anywhere
     */
    $(document).click(function() {
        $("div.searchWrapper").hide();
    });
</script>
