<?php
// This template is sent to all users of a group when that group is disbanded (deleted)
$this->Subject = "PI Group Disbanded";
?>

<p>The PI group <?php echo $data["group_name"] ?> has been disbanded by <?php echo $data["operator"]; ?>. All jobs associated with this slurm account have been killed.</p>