<?php switch ($data["modifier"]):
case "qualified": ?>
<?php $this->Subject = "User Deactivated"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been deactivated.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "ghost": ?>
<?php $this->Subject = "User Resurrected"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been resurrected.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "locked": ?>
<?php $this->Subject = "User Unlocked"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been unlocked.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "idlelocked": ?>
<?php $this->Subject = "User Unlocked"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been unlocked.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "admin": ?>
<?php $this->Subject = "User Demoted"; ?>
<p>Hello,</p>
<p>Your account on the UnityHPC Platform has been demoted from admin.</p>
<p>If you believe this to be a mistake, please reply to this email as soon as possible.</p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php default: ?>
<?php throw new \Exception("unknown modifier: " . $data["modifier"]); ?>
<?php endswitch; ?>
