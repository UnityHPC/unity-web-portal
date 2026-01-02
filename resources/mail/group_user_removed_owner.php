<?php

// This template gets sent to the owner of a group when a user is removed from that group
$this->Subject = "Group Member Removed"; ?>

<p>Hello,</p>

<p>
A user has been removed from your PI group,
'<?php echo htmlspecialchars($data["group"]); ?>'.
The details of the removed user are below:
</p>

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
