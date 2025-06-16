<?php

namespace UnityWebPortal\lib;

use Exception;

class UnitySSO
{
    private static function eppnToUID($eppn)
    {
        $eppn_output = str_replace(".", "_", $eppn);
        $eppn_output = str_replace("@", "_", $eppn_output);
        return strtolower($eppn_output);
    }

    private static function eppnToOrg($eppn)
    {
        $parts = explode("@", $eppn);
        if (count($parts) != 2) {
            throw new Exception("Malformed remote user detected: '$eppn'");
        }

        $org = $parts[1];
        $org = str_replace(".", "_", $org);
        return strtolower($org);
    }

    public static function getSSO()
    {
        if (isset($_SERVER["REMOTE_USER"])) {  // Check if SSO is enabled on this page
            $SSO = array(
                "user" => self::eppnToUID($_SERVER["REMOTE_USER"]),
                "org" => self::eppnToOrg($_SERVER["REMOTE_USER"]),
                "firstname" => $_SERVER["givenName"],
                "lastname" => $_SERVER["sn"],
                "name" => $_SERVER["givenName"] . " " . $_SERVER["sn"],
                "mail" => isset($_SERVER["mail"]) ? $_SERVER["mail"] : $_SERVER["eppn"]
            );

            return $SSO;
        }

        return null;
    }
}
