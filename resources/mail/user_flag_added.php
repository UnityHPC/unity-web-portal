<?php use UnityWebPortal\lib\UserFlag; ?>
<?php switch ($data["flag"]):
case UserFlag::QUALIFIED: ?>
<?php $this->Subject = "User Activated"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been activated. Your account details are below:</p>
<p>
<strong>Username</strong> <?php echo htmlspecialchars($data["user"]); ?>
<br>
<strong>Organization</strong> <?php echo htmlspecialchars($data["org"]); ?>
</p>
<p>
See the
<a href="<?php echo CONFIG["site"]["getting_started_url"]; ?>">Getting Started</a>
page in our documentation for next steps.
</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case UserFlag::GHOST: ?>
<?php $this->Subject = "User Deleted"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been deleted.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case UserFlag::LOCKED: ?>
<?php $this->Subject = "User Locked"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been locked.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case UserFlag::IDLELOCKED: ?>
<?php $this->Subject = "User Locked"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been locked due to inactivity.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case UserFlag::ADMIN: ?>
<?php $this->Subject = "User Promoted"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been promoted to admin.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php default: ?>
<?php throw new \Exception("unknown flag: " . $data["flag"]); ?>
<?php endswitch; ?>
