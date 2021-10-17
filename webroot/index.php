<?php
require "../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";
?>

<h1><?php echo unity_locale::ABOUT_HEADER_MAIN; ?></h1>
<hr>

<p>This page is in progress. For now, visit our <a target="_blank" href="/docs">documentation</a> for more info.</p>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>
