<?php
if (!array_key_exists("HTTP_HOST", $_SERVER)) {
    $_SERVER["HTTP_HOST"] = "worker"; // see deployment/overrides/worker
}
if (!array_key_exists("REMOTE_ADDR", $_SERVER)) {
    $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; // needed for audit log
}

require_once __DIR__ . "/../resources/autoload.php";

// UnityHTTPD::die() makes no output by default
// builtin die() makes a return code of 0, we may want nonzero
function _die(string $msg, int $exit_code)
{
    print $msg;
    exit($exit_code);
}
