<?php

// This template is sent to admins when a new group is requested
$this->Subject = "PI Group Request";
?>

<p>Hello,</p>

<p>A user has requested a PI account. User details are below:</p>

<p>
    <strong>Username</strong> <?php echo $data["user"]; ?>
    <br>
    <strong>Organization</strong> <?php echo $data["org"]; ?>
    <br>
    <strong>Name</strong> <?php echo $data["name"]; ?>
    <br>
    <strong>Email</strong> <?php echo $data["email"]; ?>
</p>

<p>You can approve this account <a href="<?php echo $this->MSG_LINKREF; ?>/admin/pi-mgmt.php">here</a>.</p>