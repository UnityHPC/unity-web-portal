<?php

namespace UnityWebPortal\lib;

use phpseclib3\Crypt\PublicKeyLoader;
use Exception;

class GithubUserNotFoundOrNoKeysException extends Exception {}

class UnitySite
{
    public function redirect($destination)
    {
        if ($_SERVER["PHP_SELF"] != $destination) {
            header("Location: $destination");
            die("Redirect failed, click <a href='$destination'>here</a> to continue.");
        }
    }

    public function getGithubKeys($username)
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

        if ((!is_array($keys)) || (count($keys) == 0)) {
            throw new GithubUserNotFoundOrNoKeysException();
        }
        return array_map(function($x){return $x["key"];}, $keys);
    }

    public function testValidSSHKey($key_str)
    {
        if ($key_str == ""){
            return false;
        }
        try {
            PublicKeyLoader::load($key_str);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
