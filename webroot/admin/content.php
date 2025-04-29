<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;

if (!$USER->isAdmin()) {
    UnitySite::unauthorized("not an admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["pageSel"])) {
        $SQL->editPage($_POST["pageSel"], $_POST["content"], $USER);
    }
}

include $LOC_HEADER;
?>

<h1>Page Content Management</h1>
<hr>

<form id="pageForm" method="POST" action="">
    <select name="pageSel" required>
        <option value="" selected disabled hidden>Select page...</option>
        <?php
        $pages = $SQL->getPages();

        foreach ($pages as $page) {
            echo "<option value='" . $page["page"] . "'>" . $page["page"] . "</option>";
        }
        ?>
    </select>

    <br><br>

    <textarea name="content" id="editor" form="pageForm"></textarea>

    <br><br>

    <input type="submit" value="Edit Page">
</form>


<script>
    ClassicEditor
        .create(document.querySelector('#editor'), {})
        .then(editor => {
            mainEditor = editor;
        })
        .catch(error => {
            console.error(error)
        });

    $("#pageForm > select[name=pageSel]").change(function(e) {
        $.ajax({url: "<?php echo $CONFIG["site"]["prefix"] ?>/admin/ajax/get_page_contents.php?pageid="
            + $(this).val(), success: function(result) {
            mainEditor.setData(result);
        }});
    });

    $("#pageForm").on("submit", function(e) {
        if (!confirm("Are you sure you want to edit this page?")) {
            e.preventDefault();
        }
    })
</script>

<?php
include $LOC_FOOTER;
