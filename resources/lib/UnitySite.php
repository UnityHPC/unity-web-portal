<?php

namespace UnityWebPortal\lib;

class UnitySite
{
    public static function redirect($destination)
    {
        if ($_SERVER["PHP_SELF"] != $destination) {
            header("Location: $destination");
            die("Redirect failed, click <a href='$destination'>here</a> to continue.");
        }
    }

    public static function getConfig($conf_path)
    {
        $arr = parse_ini_file($conf_path, true);
        return $arr;
    }
}
