<?php

// Loading branding
$branding_file_loc = __DIR__ . "/../../config/branding";
$BRANDING = parse_ini_file($branding_file_loc . "/config.ini", true);

define("DOMAIN", $_SERVER['HTTP_HOST']);
$branding_override = $branding_file_loc . "/overrides/" . DOMAIN . ".ini";
if (file_exists($branding_override)) {
    $override_config = parse_ini_file($branding_override);
    $BRANDING = array_merge($BRANDING, $override_config);
}