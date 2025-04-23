<?php

namespace UnityWebPortal\lib;

class UnityGithub
{
    public function getSshPublicKeys($username)
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
}
