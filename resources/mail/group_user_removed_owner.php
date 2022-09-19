<?php
// This template gets sent to the owner of a group when a user is removed from that group
$this->Subject = "Group Member Removed";
?>

<p>Hello,</p>

<p>A user has been removed your PI group, <?php echo $data["group"] ?>. The details of the removed user are below:</p>

<p>
<strong>Username</strong> <?php echo $data["user"]; ?>
<br>
<strong>Name</strong> <?php echo $data["name"]; ?>
<br>
<strong>Email</strong> <?php echo $data["email"]; ?>
</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>