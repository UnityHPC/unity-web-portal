<?php switch ($data["modifier"]):
case "qualified": ?>
<?php $this->Subject = "User Dequalified"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been dequalified. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "ghost": ?>
<?php $this->Subject = "User Resurrected"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been resurrected (no longer marked as ghost). </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "locked": ?>
<?php $this->Subject = "User Unlocked"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been unlocked. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "idlelocked": ?>
<?php $this->Subject = "User Idle Unlocked"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been idle unlocked. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "admin": ?>
<?php $this->Subject = "User Demoted"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been demoted from admin. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php default: ?>
<?php throw new \Exception("unknown modifier: " . $data["modifier"]); ?>
<?php endswitch; ?>
