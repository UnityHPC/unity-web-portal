<?php

require "../../resources/autoload.php";

require_once $LOC_HEADER;
?>

<h1>Support</h1>
<hr>

<?php
echo $SQL->getPage($CONFIG["page"]["support"])["content"];
?>

<?php
require_once $LOC_FOOTER;
