<?php

require "../../../resources/autoload.php";

use \phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\Common\Formats\Keys\OpenSSH;

echo "<pre>";

$private = RSA::createKey(2048);
$public = $private->getPublicKey();

echo "<section class='pubKey'>";
echo $public->toString('OpenSSH');
echo "</section>";
echo "<section class='privKey'>";
if (isset($_GET["type"]) && $_GET["type"] == "ppk") {
    echo $private->toString('PuTTY');
} else {
    echo $private;
}
echo "</section>";

echo "</pre>";