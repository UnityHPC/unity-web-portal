<?php

require "../../../resources/autoload.php";
require "../../../vendor/autoload.php";

use phpseclib3\Crypt\PublicKeyLoader;

try {
    PublicKeyLoader::load($_POST['key'], $password = false);
    echo "true";
} catch (Exception $e) {
    echo "false";
}
