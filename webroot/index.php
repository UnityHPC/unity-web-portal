<?php

require_once "../resources/autoload.php";

require_once $LOC_HEADER;
?>


<?php
echo $SQL->getPage($CONFIG["page"]["home"])["content"];
?>

<h1>Cluster Notices</h1>
<hr>

<?php

$notices = $SQL->getNotices();
foreach ($notices as $notice) {
    if ($notice["expiry"] < date('Y-m-d')) {
        $SQL->deleteNotice($notice["id"]);
        continue;
    }
    echo "<div class='notice'>";
    echo "<span class='noticeTitle'>" . $notice["title"] . "</span>";
    echo "<span class='noticeDate'>" . date('m-d-Y', strtotime($notice["date"])) . "</span>";
    echo $notice["message"];
    echo "</div>";
}

?>

<?php
require_once $LOC_FOOTER;
