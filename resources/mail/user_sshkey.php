<?php
// this template is sent when a user adds an SSH key
$this->Subject = "SSH Key Added";
?>

<p>Hello,</p>

<p>You have modified the SSH keys on your Unity account. These public keys are currently available:</p>

<?php
foreach ($data["keys"] as $key) {
    echo "<pre>$key</pre>";
}
?>

<p>You can view the SSH public keys attached to your account on the <a href="<?php echo $this->MSG_LINKREF; ?>/panel/account.php">account settings</a> page.</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>