<?php

require "../../../resources/autoload.php";

if ($SITE->testValidSSHKey($_POST["key"])){
    echo "true";
} else {
    echo "false";
}
