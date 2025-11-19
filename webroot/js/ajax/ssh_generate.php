<?php

require "../../../resources/autoload.php";

use phpseclib3\Crypt\EC;

echo "<pre>";

$private = EC::createKey('Ed25519');
$public = $private->getPublicKey();

echo "<section class='pubKey'>";
echo $public->toString('OpenSSH');
echo "</section>";
echo "<section class='privKey'>";
if (isset($_GET["type"]) && $_GET["type"] == "ppk") {
    echo $private->toString('PuTTY');
} else {
    echo $private->toString('OpenSSH');
}
echo "</section>";

echo "</pre>";
