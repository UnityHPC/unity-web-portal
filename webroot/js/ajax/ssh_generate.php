<?php

require "../../../resources/config.php";
require config::PATHS["libraries"] . "/composer/vendor/autoload.php";

use \phpseclib\Crypt\RSA;

echo "<pre>";

$rsa = new RSA();
$rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_OPENSSH);
if (isset($_GET["type"]) && $_GET["type"] == "ppk") {
    $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PUTTY);  // Set format to putty if requested
}
extract($rsa->createKey(2048));

echo "<section class='pubKey'>";
echo $publickey;
echo "</section>";
echo "<section class='privKey'>";
echo $privatekey;
echo "</section>";

echo "</pre>";