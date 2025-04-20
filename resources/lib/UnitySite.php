<?php

namespace UnityWebPortal\lib;

use phpseclib3\Crypt\PublicKeyLoader;

class UnitySite
{
    public static function redirect($destination)
    {
        if ($_SERVER["PHP_SELF"] != $destination) {
            header("Location: $destination");
            die("Redirect failed, click <a href='$destination'>here</a> to continue.");
        }
    }

    public static function removeTrailingWhitespace($arr)
    {
        $out = array();
        foreach ($arr as $str) {
            $new_string = rtrim($str);
            array_push($out, $new_string);
        }

        return $out;
    }

    public static function getGithubKeys($username)
    {
        $url = "https://api.github.com/users/$username/keys";
        $headers = array(
        "User-Agent: Unity Cluster User Portal"
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $keys = json_decode(curl_exec($curl), false);
        curl_close($curl);

        // normally returns array of objects each with a ->key attribute
        // if bad URL or no such user, returns status=404 object
        // if no keys, returns []
        if ((!is_array($keys)) || (count($keys) == 0)) {
            return [];
        }
        // phpcs:disable
        return array_map(function($x){return $x->key;}, $keys);
        // phpcs:enable
    }

    public static function testValidSSHKey($key_str)
    {
        // https://github.com/phpseclib/phpseclib/issues/2077
        // key loader still throws exception, this just mutes a warning for phpunit
        if (preg_match("/^[0-9]+$/", $key_str)) {
            return false;
        }
        // https://github.com/phpseclib/phpseclib/issues/2076
        // key loader still throws exception, this just mutes a warning for phpunit
        if (!is_null(@json_decode($key_str))) {
            return false;
        }
        try {
            PublicKeyLoader::load($key_str);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
