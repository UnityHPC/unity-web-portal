<?php

namespace UnityWebPortal\lib;

use Redis;
use Exception;

class UnityRedis
{
    private $client;

    private $enabled;

    public function __construct()
    {
        $host = CONFIG["redis"]["host"] ?? "";
        $port = CONFIG["redis"]["port"] ?? "";
        if (empty($host)) {
            $this->enabled = false;
        } else {
            $this->enabled = true;
            $this->client = new Redis();
            $this->client->connect($host, $port);
        }
    }

    public function setCache(string $object, string $key, mixed $data): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!empty($key)) {
            $keyStr = $object . "_" . $key;
        } else {
            $keyStr = $object;
        }
        if (is_null($data)) {
            UnityHTTPD::errorLog("warning", "setting '$keyStr' to null");
        }
        $this->client->set($keyStr, serialize($data));
    }

    public function getCache(string $object, string $key): mixed
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

    public function appendCacheArray(
        string $object,
        string $key,
        mixed $value,
    ): void {
        if (!$this->enabled) {
            return;
        }

        $cached_val = $this->getCache($object, $key);
        if (is_null($cached_val)) {
            $this->setCache($object, $key, [$value]);
        } else {
            if (!is_array($cached_val)) {
                throw new Exception("This cache value is not an array");
            }

            array_push($cached_val, $value);
            sort($cached_val);
            $this->setCache($object, $key, $cached_val);
        }
    }

    // TODO return void
    public function removeCacheArray(string $object, string $key, mixed $value)
    {
        if (!$this->enabled) {
            return null;
        }

        $cached_val = $this->getCache($object, $key);
        if (is_null($cached_val)) {
            $this->setCache($object, $key, []);
        } else {
            if (!is_array($cached_val)) {
                throw new Exception("This cache value is not an array");
            }

            $cached_val = array_diff($cached_val, [$value]);
            $this->setCache($object, $key, $cached_val);
        }
    }

    public function flushAll(): void
    {
        $this->client->flushAll();
    }
}
