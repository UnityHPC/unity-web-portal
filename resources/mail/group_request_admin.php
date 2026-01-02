<?php

// This template is sent to admins when a new group is requested
$this->Subject = "PI Group Request"; ?>

<p>Hello,</p>

<p>A user has requested a PI account. User details are below:</p>

<p>
    <strong>Username</strong> <?php echo htmlspecialchars($data["user"]); ?>
    <br>
    <strong>Organization</strong> <?php echo htmlspecialchars($data["org"]); ?>
    <br>
    <strong>Name</strong> <?php echo htmlspecialchars($data["name"]); ?>
    <br>
    <strong>Email</strong> <?php echo htmlspecialchars($data["email"]); ?>
</p>

<p>
You can approve this account
<?php echo getHyperlink("here", "admin/pi-mgmt.php"); ?>
.
</p>
