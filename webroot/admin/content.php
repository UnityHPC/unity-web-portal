<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;

if (!$USER->isAdmin()) {
    UnityHTTPD::forbidden("not an admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["pageSel"])) {
        $SQL->editPage($_POST["pageSel"], $_POST["content"], $USER);
    }
}

require $LOC_HEADER;
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
    const prefix = '<?php echo CONFIG["site"]["prefix"]; ?>';
    $("#pageForm > select[name=pageSel]").change(function(e) {
        $.ajax({
            url: `${prefix}/admin/ajax/get_page_contents.php?pageid=` + $(this).val(),
            success: function(result) {
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
require $LOC_FOOTER;
