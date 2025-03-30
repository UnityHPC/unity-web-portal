<?php

namespace UnityWebPortal\lib;

use phpseclib3\Crypt\PublicKeyLoader;

class UnitySite
{
    public function redirect($destination)
    {
        if ($_SERVER["PHP_SELF"] != $destination) {
            header("Location: $destination");
            die("Redirect failed, click <a href='$destination'>here</a> to continue.");
        }
    }

    public function removeTrailingWhitespace($arr)
    {
        $out = array();
        foreach ($arr as $str) {
            $new_string = rtrim($str);
            array_push($out, $new_string);
        }

        return $out;
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
        $output = json_decode(curl_exec($curl), true);
        curl_close($curl);

        $out = array();
        foreach ($output as $value) {
            array_push($out, $value["key"]);
        }

        return $out;
    }

    public function testValidSSHKey($key_str)
    {
        try {
            PublicKeyLoader::load($key_str);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
