<?php

require "../../../resources/autoload.php";

use phpseclib3\Crypt\EC;
use UnityWebPortal\lib\UnityHTTPD;

echo "<pre>";

$private = EC::createKey('Ed25519');
$public = $private->getPublicKey();

echo "<section class='pubKey'>";
echo $public->toString('OpenSSH');
echo "</section>";
echo "<section class='privKey'>";
if (UnityHTTPD::getQueryParameter("type", false) == "ppk") {
    echo $private->toString('PuTTY');
} else {
    echo $private->toString('OpenSSH');
}
echo "</section>";

echo "</pre>";
