<?php

namespace UnityWebPortal\lib;

class UnityBranding
{
    public static function getBranding($branding_loc)
    {
        // Loading branding
        $BRANDING = parse_ini_file($branding_loc . "/config.ini.default", true);
        $branding_main_override = $branding_loc . "/config.ini";
        if (file_exists($branding_main_override)) {
            $override_config_main = parse_ini_file($branding_main_override, true);
            $BRANDING = array_replace_recursive($BRANDING, $override_config_main);
        }

        $branding_override = $branding_loc . "/overrides/" . $_SERVER['HTTP_HOST'] . ".ini";
        if (file_exists($branding_override)) {
            $override_config = parse_ini_file($branding_override, true);
            $BRANDING = array_replace_recursive($BRANDING, $override_config);
        }

        return $BRANDING;
    }
}
