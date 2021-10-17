<?php

require "../../../resources/autoload.php";

include config::PATHS["templates"] . "/header.php";

?>

<style>
    #style_viewer > * {
        display: block;
    }
</style>

<div id="style_viewer">
    <h1>This is a h1</h1>
    <h2>This is a h2</h2>
    <h3>This is a h3</h3>
    <h4>This is a h4</h4>
    <h5>This is a h5</h5>
    <h6>This is a h6</h6>
    <p>This is a paragraph</p>
    <a>This is a link</a>
    <hr>
    <button>This is a button</button>
</div>

<?php

include config::PATHS["templates"] . "/footer.php";

?>