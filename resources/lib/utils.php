<?php

use UnityWebPortal\lib\exceptions\ArrayKeyException;

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
