<?php
require "../../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";
?>

<h1>Cluster Notices</h1>
<hr>

<?php

$notices = $SERVICE->sql()->getNotices();
foreach($notices as $notice) {
    echo "<div class='notice'>";
    echo "<span class='noticeTitle'>" . $notice["title"] . "</span>";
    echo "<span class='noticeDate'>" . date('m-d-Y', strtotime($notice["date"])) . "</span>";
    echo $notice["message"];
    echo "</div>";
}

?>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>