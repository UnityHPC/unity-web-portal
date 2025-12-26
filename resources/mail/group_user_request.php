<?php

// This email is sent to the requestor when they make a new request to join a group
$this->Subject = "Request Submitted"; ?>

<p>Hello,</p>

<p>You have requested to join the group <?php echo htmlspecialchars($data["group"]); ?>.</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
