<?php

namespace UnityWebPortal\lib;
use UnityWebPortal\lib\exceptions\CurlException;

class UnityGithub
{
    public function getSshPublicKeys(string $username): array
    {
        $url = "https://api.github.com/users/$username/keys";
        $headers = ["User-Agent: UnityHPC Account Portal"];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $curl_output = curl_exec($curl);
        if ($curl_output === false) {
            throw new CurlException(curl_error($curl));
        }
        $keys = jsonDecode($curl_output, false);
        curl_close($curl);

        // normally returns array of objects each with a ->key attribute
        // if bad URL or no such user, returns status=404 object
        // if no keys, returns []
        if (!is_array($keys) || count($keys) == 0) {
            return [];
        }
        // phpcs:disable
        return array_map(function ($x) {
            return $x->key;
        }, $keys);
        // phpcs:enable
    }
}
