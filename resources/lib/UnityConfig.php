<?php

namespace UnityWebPortal\lib;

class UnityConfig
{
    public static function getConfig($def_config_loc, $deploy_loc)
    {
        $CONFIG = parse_ini_file($def_config_loc . "/config.ini.default", true);
        $CONFIG = self::pullConfig($CONFIG, $deploy_loc);
        if (array_key_exists("HTTP_HOST", $_SERVER)) {
            $cur_url = $_SERVER['HTTP_HOST'];
            $url_override_path = $deploy_loc . "/overrides/" . $cur_url;
            if (is_dir($url_override_path)) {
                $CONFIG = self::pullConfig($CONFIG, $url_override_path);
            }
        }
        return $CONFIG;
    }

    private static function pullConfig($CONFIG, $loc)
    {
        $file_loc = $loc . "/config/config.ini";
        if (file_exists(($file_loc))) {
            $CONFIG_override = parse_ini_file($file_loc, true);
            return array_replace_recursive($CONFIG, $CONFIG_override);
        } else {
            return $CONFIG;
        }
    }
}
