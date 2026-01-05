<?php

// This template is sent to all users of a group when that group is disbanded (deleted)
$this->Subject = "Group Member Approved"; ?>

<p>Hello,</p>

<p>
A new user has been added to your PI group,
'<?php echo htmlspecialchars($data["group"]); ?>'.
The details of the new user are below:
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
