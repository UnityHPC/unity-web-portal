<?php
require_once "../../../resources/autoload.php";  // Load required libs

$message = $_GET["message"];
echo "<p>$message</p>";
?>

<button class="btnOkay">Okay</button>

<script>
    $("button.btnOkay").click(function() {
        $("button.btnClose").click();
    });
</script>