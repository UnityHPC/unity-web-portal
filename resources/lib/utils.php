<?php

use UnityWebPortal\lib\exceptions\EnsureException;
use UnityWebPortal\lib\exceptions\EncodingUnknownException;
use UnityWebPortal\lib\exceptions\EncodingConversionException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\NoKeyLoadedException;

// like assert() but not subject to zend.assertions config
function ensure(bool $condition, ?string $message = null): void
{
    if (!$condition) {
        throw new EnsureException($message ?? "ensure condition is false");
    }
}

/*
key must take the form "KEY_TYPE KEY_DATA OPTIONAL_COMMENT"
*/
function removeSSHKeyOptionalCommentSuffix(string $key): string
{
    $matches = [];
    if (preg_match("/^(\S+ \S+)/", $key, $matches)) {
        return $matches[1];
    } else {
        throw new \ValueError("invalid SSH key: $key");
    }
}

/**
 *  @return array of length 2: [boolean is_valid, string invalid_explanation]
 */
function testValidSSHKey(string $key): array
{
    if ($key != trim($key)) {
        return [false, "Key must not have leading or trailing whitespace"];
    }
    if (substr_count($key, "\n") != 0) {
        return [false, "Key must not span multiple lines"];
    }
    $exploded = explode(" ", $key, 2);
    if (count($exploded) == 1) {
        return [false, "Key must have at least 2 words"];
    }
    $key_type = $exploded[0];
    if (!in_array($key_type, CONFIG["ldap"]["allowed_ssh_key_types"])) {
        return [
            false,
            sprintf(
                "Key type '%s' is not allowed. Allowed key types are: %s",
                shortenString($key_type, 5, 5),
                jsonEncode(CONFIG["ldap"]["allowed_ssh_key_types"]),
            ),
        ];
    }
    try {
        PublicKeyLoader::loadPublicKey($key);
        return [true, ""];
    } catch (NoKeyLoadedException $e) {
        // phpseclib internally catches any throwable to make NoKeyLoadedException,
        // so I am not comfortable sharing the exception message with the user
        return [false, "Invalid key"];
    }
}

function jsonEncode(mixed $value, int $flags = 0, int $depth = 512): string
{
    $flags |= JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;
    return json_encode($value, $flags, $depth);
}

function jsonDecode(...$args): mixed
{
    $output = json_decode(...$args);
    if ($output === null) {
        throw new Exception("json_decode returned null");
    }
    return $output;
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
    if (!preg_match("#^\w+://#", CONFIG["site"]["url"])) {
        throw new RuntimeException('CONFIG[site][url] does not have a scheme! (ex: "https://")');
    }
    $matches = [];
    preg_match("#(^\w+://)(.*)#", CONFIG["site"]["url"], $matches);
    [$_, $site_url_scheme, $site_url_noscheme] = $matches;
    $path = join("/", [$site_url_noscheme, CONFIG["site"]["prefix"], ...$relative_url_components]);
    $path_normalized = pathNormalize($path);
    return $site_url_scheme . $path_normalized;
}

function getHyperlink($text, ...$url_components)
{
    $text = htmlspecialchars($text);
    $url = getURL(...$url_components);
    return "<a href='$url'>$text</a>";
}

/**
 * negative numbers not allowed
 * extra args (ex: base) are passed along to intval()
 * @throws ValueError
 */
function digits2int(string $x, ...$args): int
{
    if (ctype_digit($x)) {
        return intval($x, ...$args);
    } else {
        throw new ValueError("not digits: $x");
    }
}

/* example with 3 leading chars and 3 trailing chars: "foobarbaz" -> "foo...baz" */
function shortenString(
    string $x,
    int $leading_chars,
    int $trailing_chars,
    string $ellipsis = "...",
): string {
    if ($leading_chars + strlen($ellipsis) + $trailing_chars > strlen($x)) {
        return $x;
    }
    return substr($x, 0, $leading_chars) .
        $ellipsis .
        substr($x, -1 * $trailing_chars, $trailing_chars);
}

function getTemplatePath(string $basename)
{
    $template_path = __DIR__ . "/../templates/$basename";
    $override_path = __DIR__ . "/../../deployment/templates_overrides/$basename";
    if (file_exists($override_path)) {
        return $override_path;
    }
    return $template_path;
}
