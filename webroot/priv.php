<?php

require_once "../resources/autoload.php";

require_once $LOC_HEADER;
?>

<h1>Unity Cluster Site Policy</h1>
<hr>

<?php
echo $SQL->getPage($CONFIG["page"]["policy"])["content"];
?>

<?php
require_once $LOC_FOOTER;
