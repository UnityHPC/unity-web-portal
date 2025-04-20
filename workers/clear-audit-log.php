<?php

require_once __DIR__ . "/../resources/autoload.php";
require_once __DIR__ . "/../resources/init.php";

# Days to keep
$days = 30;

$daysAgo = date('Y-m-d', strtotime("-$days days"));

$SQL->getConn()->prepare("DELETE FROM audit_log WHERE timestamp < :daysAgo")->execute(['daysAgo' => $daysAgo]);
