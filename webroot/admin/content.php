<?php
require "../../resources/autoload.php";

if (!$USER->isAdmin()) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

}

include LOC_HEADER;
?>

<h1>Page Content Management</h1>
<hr>


<?php
include LOC_FOOTER;
?>