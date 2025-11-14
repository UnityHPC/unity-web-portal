<?php

// this template is sent when a user account becomes qualified
$this->Subject = "User Activated"; ?>

<p>Hello,</p>

<p>Your account on the Unity cluster has been activated. Your account details are below:</p>

<p>
<strong>Username</strong> <?php echo $data["user"]; ?>
<br>
<strong>Organization</strong> <?php echo $data["org"]; ?>
</p>

<p>Please login to the web portal to access Unity.
    If you need console access, you will need to set your SSH keys in the
    <a href="<?php echo $this->MSG_LINKREF; ?>/panel/account.php">account settings</a> page.</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
