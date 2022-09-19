<?php
// this template is sent to the requestor of a group which has just been denied
$this->Subject = "PI Group Denied";
?>

<p>The PI group <?php echo $data["group"]; ?> was denied by <?php echo $data["operator"]; ?>.</p>