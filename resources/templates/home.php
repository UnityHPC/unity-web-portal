<h1>Welcome</h1>
<p>
    Welcome to the UnityHPC Platform Account Portal.
    Here you can manage your SSH keys, join and leave PI groups, manage your own PI group, and more.
</p>

<?php
if (!($_SESSION["user_exists"] ?? false)) {
    $hyperlink = getHyperlink("Log In", "panel/account.php");
    echo "<p>Please $hyperlink for more information.</p>";
}
?>

<br>

<h2>Cluster Notices</h2>

<?php

$notices = $SQL->getNotices();
foreach ($notices as $notice) {
    echo "<div class='notice'>";
    echo "<span class='noticeTitle'>" . $notice["title"] . "</span>";
    echo "<span class='noticeDate'>" . date('m-d-Y', strtotime($notice["date"])) . "</span>";
    echo $notice["message"];
    echo "</div>";
}

?>
