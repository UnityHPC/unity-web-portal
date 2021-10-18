<?php
require "../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";
?>

<h1><?php echo unity_locale::AUTH_HEADER_MAIN; ?></h1>
<hr>

<div id="idpSelect"></div>

<script src="/js/eds/idpselect_config.js" type="text/javascript" language="javascript"></script>
<script src="/js/eds/idpselect.js" type="text/javascript" language="javascript"></script>
<link rel="stylesheet" type="text/css" href="/js/eds/idpselect.css">

<style>
label {
    margin: 0;
}
</style>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>
