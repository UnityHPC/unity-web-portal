<?php

namespace UnityWebPortal\lib;

class UnityRedis
{
    private $client;

    public function __construct($host, $port)
    {
        $this->client = new \Redis();
        $this->client->connect($host, $port);
    }

    public function setCache($object, $key, $data)
    {
        if (!empty($key)) {
            $keyStr = $object . "_" . $key;
        } else {
            $keyStr = $object;
        }

        $this->client->set($keyStr, serialize($data));
    }

    public function getCache($object, $key)
    {
        if (!empty($key)) {
            $keyStr = $object . "_" . $key;
        } else {
            $keyStr = $object;
        }

        $cached_val = $this->client->get($keyStr);
        if ($cached_val) {
            return unserialize($cached_val);
        }

        return null;
    }
}
