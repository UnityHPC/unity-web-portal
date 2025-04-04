<?php

require "../../../resources/autoload.php";

use phpseclib3\Crypt\RSA;
if ($_SERVER['REQUEST_METHOD'] != "GET") {
    $SITE->bad_request("invalid request method '" . $_SERVER["REQUEST_METHOD"] . "'");
}

$private = RSA::createKey(2048);
$public = $private->getPublicKey();

switch ($_GET["type"]) {
    case "key":
        break;
    case "ppk":
        $private = $private->toString("PuTTY");
        break;
    default:
        $SITE->bad_request("invalid type '" . $_GET["type"] . "'");
}

echo json_encode([
    "pubkey" => $public->toString('OpenSSH'),
    "privkey" => $private
]);
