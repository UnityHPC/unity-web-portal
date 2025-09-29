<?php

use UnityWebPortal\lib\exceptions\ArrayKeyException;
use UnityWebPortal\lib\exceptions\EnsureException;
use phpseclib3\Crypt\PublicKeyLoader;

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
                implode("", array_map(fn($x) => jsonEncode([$x]), $keysTraversed))
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

function testValidSSHKey($key_str)
{
    // key loader still throws, these just mute warnings for phpunit
    // https://github.com/phpseclib/phpseclib/issues/2079
    if ($key_str == "") {
        return false;
    }
    // https://github.com/phpseclib/phpseclib/issues/2076
    // https://github.com/phpseclib/phpseclib/issues/2077
    // there are actually valid JSON keys (JWK), but I don't think anybody uses it
    if (!is_null(@json_decode($key_str))) {
        return false;
    }
    try {
        PublicKeyLoader::load($key_str);
        return true;
        // phpseclib should throw only NoKeyLoadedException but that is not the case
        // https://github.com/phpseclib/phpseclib/pull/2078
        // } catch (\phpseclib3\Exception\NoKeyLoadedException $e) {
    } catch (\Throwable $e) {
        return false;
    }
}

function jsonEncode($value, $flags = 0, $depth = 512)
{
    $flags |= JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;
    return json_encode($value, $flags, $depth);
}

function mbConvertEncoding($string, $to_encoding, $from_encoding)
{
    $output = mb_convert_encoding($string, $to_encoding, $from_encoding);
    if ($output === false) {
        throw new EncodingConversionException(
            jsonEncode(
                ["to" => $to_encoding, "from" => $from_encoding, "base64" => base64_encode($string)]
            )
        );
    }
    return $output;
}

function mbDetectEncoding($string, $encodings = null, $_ = null)
{
    $output = mb_detect_encoding($string, $encodings, strict: true);
    if ($output === false) {
        throw new EncodingUnknownException(base64_encode($string));
    }
    return $output;
}
