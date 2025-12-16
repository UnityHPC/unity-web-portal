<?php

// this template is sent when a user account becomes qualified
$this->Subject = "User Activated"; ?>

<p>Hello,</p>

<p>Your account on the UnityHPC Platform has been activated. Your account details are below:</p>

<p>
<strong>Username</strong> <?php echo $data["user"]; ?>
<br>
<strong>Organization</strong> <?php echo $data["org"]; ?>
</p>

<p>
See the
<a href="<?php echo CONFIG["site"]["getting_started_url"]; ?>">Getting Started</a>
page in our documentation for next steps.
</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
