<?php
// This template is sent to the group owner of the newly approved group
$this->Subject = "PI Group Approved";
?>

<p>The PI group <?php echo $data["group"]; ?> was approved by <?php echo $data["operator"]; ?>.</p>