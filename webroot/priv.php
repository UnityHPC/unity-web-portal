<?php
require "../resources/autoload.php";

require_once LOC_HEADER;
?>

<h1>Unity Cluster Site Policy</h1>

<?php
echo $SQL->getPage($BRANDING["page"]["priv"])["content"];
?>

<?php
require_once LOC_FOOTER;
?>
