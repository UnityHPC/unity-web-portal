<?php

use UnityWebPortal\lib\exceptions\ArrayKeyException;
use UnityWebPortal\lib\exceptions\EnsureException;
use UnityWebPortal\lib\exceptions\EncodingUnknownException;
use UnityWebPortal\lib\exceptions\EncodingConversionException;
use phpseclib3\Crypt\PublicKeyLoader;

// like assert() but not subject to zend.assertions config
function ensure(bool $condition, ?string $message = null): void
{
    if (!$condition) {
        throw new EnsureException($message ?? "ensure condition is false");
    }
}

function testValidSSHKey(string $key_str): bool
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

function jsonEncode(mixed $value, int $flags = 0, int $depth = 512): string
{
    $flags |= JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;
    return json_encode($value, $flags, $depth);
}

function mbConvertEncoding(
    string $string,
    string $to_encoding,
    ?string $from_encoding = null,
): string {
    $output = mb_convert_encoding($string, $to_encoding, $from_encoding);
    if ($output === false) {
        throw new EncodingConversionException(
            jsonEncode([
                "to" => $to_encoding,
                "from" => $from_encoding,
                "base64" => base64_encode($string),
            ]),
        );
    }
    return $output;
}

function mbDetectEncoding(string $string, ?array $encodings = null, mixed $_ = null): string
{
    $output = mb_detect_encoding($string, $encodings, strict: true);
    if ($output === false) {
        throw new EncodingUnknownException(base64_encode($string));
    }
    return $output;
}

/* https://stackoverflow.com/a/15575293/18696276 */
function pathNormalize(string $path)
{
    return preg_replace("#/+#", "/", $path);
}

function getURL(...$relative_url_components)
{
    $url_components = array_merge(
        [CONFIG["site"]["url"], CONFIG["site"]["prefix"]],
        $relative_url_components,
    );
    $url = join("/", $url_components);
    // if URL starts with a "scheme" like "https://", do not try to alter the slashes in the scheme
    if (preg_match("#^\w+://#", $url)) {
        $matches = [];
        preg_match("#(^\w+://)(.*)#", $url, $matches);
        return $matches[1] . pathNormalize($matches[2]);
    } else {
        return pathNormalize($url);
    }
}

function getHyperlink($text, ...$url_components)
{
    $text = htmlspecialchars($text);
    $url_components = array_map("htmlspecialchars", $url_components);
    $url = getURL(...$url_components);
    return "<a href='$url'>$text</a>";
}
