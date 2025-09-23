<?php

use UnityWebPortal\lib\exceptions\ArrayKeyException;
use UnityWebPortal\lib\exceptions\EnsureException;

function arrayGet($array, ...$keys)
{
    $cursor = $array;
    $keysTraversed = [];
    foreach ($keys as $key) {
        array_push($keysTraversed, $key);
        if (!isset($cursor[$key])) {
            throw new ArrayKeyException(
                "key not found: \$array" .
                // [1, 2, "foo"] => [1][2]["foo"]
                implode("", array_map(fn($x) => json_encode([$x]), $keysTraversed))
            );
        }
        $cursor = $cursor[$key];
    }
    return $cursor;
}

// like assert() but not subject to zend.assertions config
function ensure(bool $condition, ?string $message = null)
{
    if (!$condition) {
        throw new EnsureException($message ?? "ensure condition is false");
    }
}
