<?php

require_once __DIR__ . "/../../resources/autoload.php";

require $LOC_HEADER;
?>

<h1>Support</h1>
<hr>

<?php
echo $SQL->getPage(CONFIG["page"]["support"])["content"];
?>

<?php
require_once $LOC_FOOTER;
