<?php

// this template is sent when a user modifies an SSH key
$this->Subject = "SSH Key Modified"; ?>

<p>Hello,</p>

<p>
You have modified the SSH keys on your Unity account. These public keys are currently available:
</p>

<?php foreach ($data["keys"] as $key) {
    echo "<pre>" . htmlspecialchars($key) . "</pre>";
} ?>

<p>
You can view the SSH public keys attached to your account on the
<?php echo getHyperlink("account settings", "/panel/account.php"); ?>
page.
</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
