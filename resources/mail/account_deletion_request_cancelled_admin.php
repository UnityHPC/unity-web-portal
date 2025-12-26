<?php

$this->Subject = "Account Deletion Request Cancelled"; ?>

<p>Hello,</p>

<p>A user has cancelled their request for account deletion. User details are below:</p>

<p>
    <strong>Username</strong> <?php echo htmlspecialchars($data["user"]); ?>
    <br>
    <strong>Name</strong> <?php echo htmlspecialchars($data["name"]); ?>
    <br>
    <strong>Email</strong> <?php echo htmlspecialchars($data["email"]); ?>
</p>
