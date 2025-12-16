<?php switch ($data["modifier"]):
case "qualified": ?>
<?php $this->Subject = "User Activated"; ?>
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
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "ghost": ?>
<?php $this->Subject = "User Deleted"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been deleted.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "locked": ?>
<?php $this->Subject = "User Locked"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been locked.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "idlelocked": ?>
<?php $this->Subject = "User Locked"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been locked due to inactivity.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "admin": ?>
<?php $this->Subject = "User Promoted"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been promoted to admin.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php default: ?>
<?php throw new \Exception("unknown modifier: " . $data["modifier"]); ?>
<?php endswitch; ?>
