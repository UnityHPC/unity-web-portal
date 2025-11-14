<?php

namespace UnityWebPortal\lib;

use Redis;
use TypeError;

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
        callable $default_value_getter,
    ): void {
        if (!$this->enabled) {
            return;
        }
        $old_val = $this->getCache($object, $key) ?? $default_value_getter();
        if (!is_array($old_val)) {
            throw new TypeError("redis[$object][$key] is not an array!");
        }
        $new_val = $old_val;
        array_push($new_val, $value);
        sort($new_val);
        $this->setCache($object, $key, $new_val);
    }

    public function removeCacheArray(
        string $object,
        string $key,
        mixed $value,
        callable $default_value_getter,
    ) {
        if (!$this->enabled) {
            return;
        }
        $old_val = $this->getCache($object, $key) ?? $default_value_getter();
        if (!is_array($old_val)) {
            throw new TypeError("redis[$object][$key] is not an array!");
        }
        $new_val = array_diff($old_val, [$value]);
        sort($new_val);
        $this->setCache($object, $key, $new_val);
    }

    public function flushAll(): void
    {
        $this->client->flushAll();
    }
}
