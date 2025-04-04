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

    public function array_get_or_bad_request(mixed $key, array $array){
        if (!array_key_exists($key, $array)){
            $this->bad_request("missing $key");
        }
        return $array[$key];
    }

    public function bad_request($msg){
        header("HTTP/1.1 400 Bad Request");
        $full_msg = "<pre>ERROR: bad request. Please contact Unity support.\n$msg</pre>";
        error_log($full_msg);
        error_log((new Exception())->getTraceAsString());
        die($full_msg);
    }

    public function alert(string $msg): void{
        echo "<script type='text/javascript'>alert(" . json_encode($msg) . ");</script>";
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
        return array_map(function($x){return $x->key;}, $keys);
    }

    public function testValidSSHKey(string $key_str)
    {
        $key_str = trim($key_str);
        if ($key_str == ""){
            return false;
        }
        try {
            PublicKeyLoader::load($key_str);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
