<?php

require "../../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;
use phpseclib3\Crypt\PublicKeyLoader;

echo (UnitySite::testValidSSHKey($_POST["key"]) ? "true" : "false");
