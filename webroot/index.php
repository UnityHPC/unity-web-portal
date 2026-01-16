<?php

require_once __DIR__ . "/../resources/autoload.php";

require $LOC_HEADER;
?>


<h1>Welcome</h1>
<p>
    Welcome to the UnityHPC Platform Account Portal.
    Here you can manage your SSH keys, join and leave PI groups, manage your own PI group, and more.
    Please <a href="<?php echo getURL("panel/account.php"); ?>">Log In</a> for more information.
</p>
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

<?php require $LOC_FOOTER; ?>
