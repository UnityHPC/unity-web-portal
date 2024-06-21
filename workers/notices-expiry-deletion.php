<?php

require_once "../resources/autoload.php";
require_once "../resources/init.php";

$SQL->getConn()->prepare("DELETE FROM `notices` WHERE `expiry` <= CURDATE()")->execute();
