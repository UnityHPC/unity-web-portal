<?php

// this template is sent to the group owner when they deny a user from joining their group
$this->Subject = "Group Member Denied"; ?>

<p>Hello,</p>

<p>A user has been denied from joining your PI group, <?php echo htmlspecialchars($data["group"]); ?>.
The details of the denied user are below:</p>

<p>
<strong>Username</strong> <?php echo htmlspecialchars($data["user"]); ?>
<br>
<strong>Organization</strong> <?php echo htmlspecialchars($data["org"]); ?>
<br>
<strong>Name</strong> <?php echo htmlspecialchars($data["name"]); ?>
<br>
<strong>Email</strong> <?php echo htmlspecialchars($data["email"]); ?>
</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
