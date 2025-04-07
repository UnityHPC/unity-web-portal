<?php

require "../../../resources/autoload.php";

use phpseclib3\Crypt\RSA;

$private = RSA::createKey(2048);
$public = $private->getPublicKey();

switch ($_GET["type"]) {
    case "key":
        break;
    case "ppk":
        $private = $private->toString("PuTTY");
        break;
}

echo json_encode([
    "pubkey" => $public->toString('OpenSSH'),
    "privkey" => $private
]);


