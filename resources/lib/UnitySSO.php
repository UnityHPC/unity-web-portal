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

    // shibboleth service provider writes attributes into "server variables"
    // shibboleth service provider does not garuntee attributes are set, even REMOTE_USER
    // https://shibboleth.atlassian.net/wiki/spaces/SP3/pages/2065335257/AttributeAccess
    // I have observed attributes to be set to empty strings while shibd complains of bad config
    private static function getAttributeRaw($attributeName, $fallbackAttributeName = null)
    {
        if (isset($_SERVER[$attributeName]) && $_SERVER[$attributeName] != "") {
            return $_SERVER[$attributeName];
        }
        if (is_null($fallbackAttributeName)) {
            throw new SSOException("\$_SERVER[\"$attributeName\"] is unset or empty!");
        }
        if (isset($_SERVER[$fallbackAttributeName]) && $_SERVER[$fallbackAttributeName] != "") {
            return $_SERVER[$fallbackAttributeName];
        }
        throw new SSOException(
            "\$_SERVER[\"$attributeName\"] and \$_SERVER[\"$fallbackAttributeName\"]"
            . " are both unset or empty!"
        );
    }

    private static function getAttribute($attributeName, $fallbackAttributeName = null)
    {
        $attribute_raw = self::getAttributeRaw($attributeName, $fallbackAttributeName);
        // attributes may have multiple values, by default they are split by ';'
        // see SPConfig setting attributeValueDelimiter
        return explode(";", $attribute_raw)[0];
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
