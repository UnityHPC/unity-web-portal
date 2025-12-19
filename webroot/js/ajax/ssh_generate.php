<?php

require "../../../resources/autoload.php";

use phpseclib3\Crypt\EC;
use UnityWebPortal\lib\UnityHTTPD;

$private = EC::createKey('Ed25519');
$public = $private->getPublicKey();
$public_str = $public->toString('OpenSSH');
if (($_GET["type"] ?: null) == "ppk") {
    $private_str = $private->toString('PuTTY');
} else {
    $private_str = $private->toString('OpenSSH');
}
header('Content-Type: application/json; charset=utf-8');
echo jsonEncode(["public" => $public_str, "private" => $private_str]);
