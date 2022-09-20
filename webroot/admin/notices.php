<?php
require "../../resources/autoload.php";

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
}

include LOC_HEADER;
?>

<h1>Cluster Notice Management</h1>
<hr>

<h5>Create New Cluster Notice</h5>
<hr>

<form action="" method="POST">
    <input type="text" name="title" placeholder="Notice Title">
    <input type="date" name="date">
    <textarea name="content" id="editor"></textarea>
    <input type="submit" value="Create New Notice">
</form>

<div class='example notice'>
    <span class='noticeTitle'>Edit Title To Change</span>
    <span class='noticeDate'>Edit Date to Change</span>
    <div>Edit Message to Change</div>
</div>

<h5>Existing Notices</h5>
<hr>

<?php

$notices = $SQL->getNotices();
foreach($notices as $notice) {
    echo "<div class='notice'>";
    echo "<span class='noticeTitle'>" . $notice["title"] . "</span>";
    echo "<span class='noticeDate'>" . date('m-d-Y', strtotime($notice["date"])) . "</span>";
    echo $notice["message"];
    echo "</div>";
}

?>

<script>
    ClassicEditor
        .create(document.querySelector('#editor'))
        .then(editor => {
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
</script>

<?php
include LOC_FOOTER;
?>