<?php

// This mail get's sent to users when they are removed a PI group
$this->Subject = "Removed from Group"; ?>

<p>Hello,</p>

<p>You have been removed from the PI group <?php echo htmlspecialchars($data["group"]); ?>.</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
