<?php

require_once __DIR__ . "/../resources/autoload.php";

require $LOC_HEADER;
?>


<?php
echo $SQL->getPage(CONFIG["page"]["home"])["content"];
?>

<?php
require_once $LOC_FOOTER;
