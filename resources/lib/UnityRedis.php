<?php

namespace UnityWebPortal\lib;

use Redis;
use Exception;

class UnityRedis
{
    private $client;

    private $enabled;

    public function __construct($host, $port)
    {
        if (empty($host)) {
            $this->enabled = false;
        } else {
            $this->enabled = true;
            $this->client = new Redis();
            $this->client->connect($host, $port);
        }
    }

    public function setCache($object, $key, $data)
    {
        if (!$this->enabled) {
            return;
        }

        if (!empty($key)) {
            $keyStr = $object . "_" . $key;
        } else {
            $keyStr = $object;
        }

        $this->client->set($keyStr, serialize($data));
    }

    public function getCache($object, $key)
    {
        if (!$this->enabled) {
            return null;
        }

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

    public function appendCacheArray($object, $key, $value)
    {
        if (!$this->enabled) {
            return;
        }

        $cached_val = $this->getCache($object, $key);
        if (is_null($cached_val)) {
            $this->setCache($object, $key, array($value));
        } else {
            if (!is_array($cached_val)) {
                throw new Exception("This cache value is not an array");
            }

            array_push($cached_val, $value);
            sort($cached_val);
            $this->setCache($object, $key, $cached_val);
        }
    }

    public function removeCacheArray($object, $key, $value)
    {
        if (!$this->enabled) {
            return null;
        }

        $cached_val = $this->getCache($object, $key);
        if (is_null($cached_val)) {
            $this->setCache($object, $key, array());
        } else {
            if (!is_array($cached_val)) {
                throw new Exception("This cache value is not an array");
            }

            $cached_val = array_diff($cached_val, array($value));
            $this->setCache($object, $key, $cached_val);
        }
    }

    public function flushAll(){
        $this->client->flushAll();
    }
}
