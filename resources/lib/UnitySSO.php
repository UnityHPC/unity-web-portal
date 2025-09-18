<?php

namespace UnityWebPortal\lib;

use Exception;
use UnityWebPortal\lib\exceptions\SSOException;

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
            throw new SSOException("Malformed remote user detected: '$eppn'");
        }

        $org = $parts[1];
        $org = str_replace(".", "_", $org);
        return strtolower($org);
    }

    // shibboleth service provider writes attribute into "server variables":
    // https://shibboleth.atlassian.net/wiki/spaces/SP3/pages/2065335257/AttributeAccess#PHP-Header-Access
    private static function getAttribute($attributeName, $fallbackAttributeName = null)
    {
        if (!is_null($fallbackAttributeName) && !(isset($_SERVER[$attributeName]))) {
            $attribute = UnitySite::arrayGetOrBadRequest($_SERVER, $fallbackAttributeName);
        } else {
            $attribute = UnitySite::arrayGetOrBadRequest($_SERVER, $attributeName);
        }
        // shib attributes may have multiple values, by default they are split by ';'
        // see SPConfig setting attributeValueDelimiter
        return explode(";", $attribute)[0];
    }

    public static function getSSO()
    {
        return array(
            "user" => self::eppnToUID(self::getAttribute("REMOTE_USER")),
            "org" => self::eppnToOrg(self::getAttribute("REMOTE_USER")),
            "firstname" => self::getAttribute("givenName"),
            "lastname" => self::getAttribute("sn"),
            "name" => self::getAttribute("givenName") . " " . self::getAttribute("sn"),
            "mail" => self::getAttribute("mail", "eppn")
        );
    }
}
