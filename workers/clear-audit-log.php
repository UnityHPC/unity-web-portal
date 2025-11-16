#!/usr/bin/env php
<?php
$_SERVER["HTTP_HOST"] = "worker"; // see deployment/overrides/worker

require_once __DIR__ . "/../resources/autoload.php";

// Days to keep
$days = 30;

$daysAgo = date("Y-m-d", strtotime("-$days days"));

$SQL->getConn()
    ->prepare("DELETE FROM audit_log WHERE timestamp < :daysAgo")
    ->execute(["daysAgo" => $daysAgo]);

