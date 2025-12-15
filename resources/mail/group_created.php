<?php

// This template is sent to the group owner of the newly approved group
$this->Subject = "PI Account Approved"; ?>

<p>Hello,</p>

<p>
Your request for a PI account on the Unity cluster has been approved.
You can access the management page for your group
<?php echo getHyperlink("on this page", "panel/pi.php"); ?>.
</p>

<p>Do not hesitate to reply if you have any questions!</p>
