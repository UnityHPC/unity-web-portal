<?php

// this email is sent to the user who made the request to join the group when they are denied
$this->Subject = "Group Request Denied";
?>

<p>Hello,</p>

<p>You have been denied from joining the PI group <?php echo $data["group"]; ?>.</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>