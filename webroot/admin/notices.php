<?php

require_once "../../resources/autoload.php";

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($_POST["form_type"]) {
        case "newNotice":
            $SQL->addNotice($_POST["title"], $_POST["date"], $_POST["content"], $_POST["expiry"], $USER);

            break;
        case "editNotice":
            $SQL->editNotice($_POST["id"], $_POST["title"], $_POST["date"], $_POST["content"], $_POST["expiry"]);

            break;
        case "delNotice":
            $SQL->deleteNotice($_POST["id"]);

            break;
    }
}

include $LOC_HEADER;
?>

<h1>Cluster Notice Management</h1>
<hr>

<h5>Create/Edit Cluster Notice</h5>

<button style='display: none;' class='btnClear'>Create New Notice Instead</button>

<form action="" method="POST" id="noticeForm">
    <input type="hidden" name=id>
    <input type="hidden" name="form_type" value="newNotice">
    <input type="text" name="title" placeholder="Notice Title">
    <input type="date" name="date">
    <textarea name="content" id="editor" form="noticeForm"></textarea>
    <input style='display: inline-block;' type="submit" value="Create Notice">
</form>

<div class='example notice'>
    <span class='noticeTitle'>Edit Title To Change</span>
    <span class='noticeDate'>Edit Date to Change</span>
    <div class='noticeText'><p>Edit Message to Change</p></div>
</div>

<hr>
<h5>Existing Notices</h5>

<?php

$notices = $SQL->getNotices();
foreach ($notices as $notice) {
    echo "<div class='notice' data-id='" . $notice["id"] . "'>";
    echo "<span class='noticeTitle'>" . $notice["title"] . "</span>";
    echo "<span class='noticeDate'>" . date('Y-m-d', strtotime($notice["date"])) . "</span>";
    echo "<div class='noticeText'>" . $notice["message"] . "</div>";
    echo "<button class='btnEdit'>Edit</button>";
    echo
    "<form style='display: inline-block; margin-left: 10px;' method='POST' action=''>
    <input type='hidden' name='form_type' value='delNotice'>
    <input type='hidden' name='id' value='" . $notice["id"] . "'>
    <input type='submit' value='Delete'>
    </form>";
    echo "</div>";
}

?>

<script>
    ClassicEditor
        .create(document.querySelector('#editor'), {
            //toolbar: [ 'bold', 'italic', 'link', 'undo', 'redo', 'numberedList', 'bulletedList' ]
            removePlugins: ['image']
        })
        .then(editor => {
            mainEditor = editor;
            editor.model.document.on('change:data', () => {
                $("div.example > div").html(editor.getData());
            });
        })
        .catch(error => {
            console.error(error)
        });

    $('input[name=title]').on('input', function(e) {
        $("div.example > span.noticeTitle").text($(this).val());
    });

    $('input[name=date]').on('input', function(e) {
        $("div.example > span.noticeDate").text($(this).val());
    });

    $('button.btnEdit').on('click', function(e) {
        let cur_id = $("#noticeForm > input[name=id]").val();
        let cur_title = $("#noticeForm > input[name=title]").val();
        let cur_date = $("#noticeForm > input[name=date]").val();
        let cur_text = mainEditor.getData();
        var hasUnsavedWork =
            cur_id != "" ||
            cur_title != "" ||
            cur_date != "" ||
            cur_text != "";

        if (hasUnsavedWork) {
            if (!confirm("Are you sure you want to clear your unsaved work?")) {
                return;
            }
        }

        var id = $(this).parent().attr("data-id");
        var title = $(this).siblings("span.noticeTitle").text();
        var date = $(this).siblings("span.noticeDate").text();
        var text = $(this).siblings("div.noticeText").html();

        $("#noticeForm > input[name=id]").val(id);
        $("#noticeForm > input[name=title]").val(title);
        $("#noticeForm > input[name=date]").val(date);
        $("#noticeForm > input[name=form_type").val("editNotice")
        mainEditor.setData(text);

        $("#noticeForm > input[type=submit]").val("Edit Notice");
        $("button.btnClear").show();
    });

    $('button.btnClear').on('click', function(e) {
        if (!confirm("Are you sure you want to clear this edit?")) {
            return;
        }

        $("#noticeForm > input[name=id]").val("");
        $("#noticeForm > input[name=title]").val("");
        $("#noticeForm > input[name=date]").val("");
        $("#noticeForm > input[name=form_type").val("newNotice")
        mainEditor.setData("");

        $("#noticeForm > input[type=submit]").val("Create Notice");
        $(this).hide();
    });

    $("#noticeForm").on("submit", function(e) {
        if (!confirm("Are you sure you want to add/edit notice?")) {
            e.preventDefault();
            return;
        }
    });
</script>

<?php
include $LOC_FOOTER;
