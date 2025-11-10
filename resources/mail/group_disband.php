<?php

// This template is sent to all users of a group when that group is disbanded (deleted)
$this->Subject = "PI Group Disbanded"; ?>

<p>Hello,</p>

<p>Your PI group, <?php echo $data["group_name"]; ?>, has been disbanded on the Unity
cluster. Any jobs associated with this PI account have been killed.</p>

<p>If you believe this to be a mistake, please reply to this email</p>
