<?php

// this template is sent when a user sets their login shell
$this->Subject = "Login Shell Updated";
?>

<p>Hello,</p>

<p>You have updated your login shell on the Unity cluster to <?php echo $data["new_shell"]; ?>.
You can view the login shell settings on the
<a href="<?php echo $this->MSG_LINKREF; ?>/panel/account.php">account settings</a> page</p>

<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
