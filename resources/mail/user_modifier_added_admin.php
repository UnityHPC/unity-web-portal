<?php switch ($data["modifier"]):
case "qualified": ?>
<?php $this->Subject = "User Qualified"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been qualified. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "ghost": ?>
<?php $this->Subject = "User Ghosted"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been marked as ghost. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "locked": ?>
<?php $this->Subject = "User Locked"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been locked. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "idlelocked": ?>
<?php $this->Subject = "User Idle Locked"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been idle locked. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php case "admin": ?>
<?php $this->Subject = "User Promoted"; ?>
<p>Hello,</p>
<p>User "<?php echo $data["user"] ?>" has been promoted to admin. </p>
<?php break; ?>

<?php /////////////////////////////////////////////////////////////////////////////////////////// ?>
<?php default: ?>
<?php throw new \Exception("unknown modifier: " . $data["modifier"]); ?>
<?php endswitch; ?>
