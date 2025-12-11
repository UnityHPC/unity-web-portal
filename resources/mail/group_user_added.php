<?php

// This template is sent to a user when they have been added to a group
$this->Subject = "Group Request Approved"; ?>

<p>Hello,</p>

<p>You have been approved to join the PI group <?php echo $data["group"]; ?>.
Navigate to the <a href="<?php echo getURL("panel/groups.php"); ?>">my groups</a>
page to see your PI groups.</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
