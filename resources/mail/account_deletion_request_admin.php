<?php

$this->Subject = "Account Deletion Request"; ?>

<p>Hello,</p>

<p>A user has requested deletion of their account. User details are below:</p>

<p>
    <strong>Username</strong> <?php echo $data["user"]; ?>
    <br>
    <strong>Name</strong> <?php echo $data["name"]; ?>
    <br>
    <strong>Email</strong> <?php echo $data["email"]; ?>
</p>
