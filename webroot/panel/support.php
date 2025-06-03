<?php

require "../../resources/autoload.php";

include $LOC_HEADER;
?>

<h1>Support</h1>
<hr>

<?php
echo $SQL->getPage($CONFIG["page"]["support"])["content"];
?>

<?php
require_once $LOC_FOOTER;
