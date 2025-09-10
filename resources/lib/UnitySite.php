<?php

namespace UnityWebPortal\lib;

use phpseclib3\Crypt\PublicKeyLoader;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;

class UnitySite
{
    public static function die($x = null, $show_user = false)
    {
        if (isset($GLOBALS["PHPUNIT_NO_DIE_PLEASE"]) && $GLOBALS["PHPUNIT_NO_DIE_PLEASE"] == true) {
            if (is_null($x)) {
                throw new PhpUnitNoDieException();
            } else {
                throw new PhpUnitNoDieException($x);
            }
        } else {
            if (!is_null($x) and $show_user) {
                die($x);
            } else {
                die();
            }
        }
    }

    public static function redirect($destination)
    {
        header("Location: $destination");
        self::die("Redirect failed, click <a href='$destination'>here</a> to continue.", true);
    }

    private static function headerResponseCode(int $code, string $reason)
    {
        $protocol = @$_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1";
        $msg = $protocol . " " . strval($code) . " " . $reason;
        header($msg, true, $code);
    }

    public static function errorLog(string $title, string $message)
    {
        if (@$GLOBALS["PHPUNIT_SIMPLE_ERROR_LOG_PLEASE"] == true) {
            error_log("$title: $message");
            return;
        }
        error_log(
            "$title: " . json_encode(
                [
                    "message" => $message,
                    "REMOTE_USER" => @$_SERVER["REMOTE_USER"],
                    "REMOTE_ADDR" => @$_SERVER["REMOTE_ADDR"],
                    "trace" => (new \Exception())->getTraceAsString()
                ]
            )
        );
    }

    public static function badRequest($message)
    {
        self::headerResponseCode(400, "bad request");
        self::errorLog("bad request", $message);
        self::die($message);
    }

    public static function forbidden($message)
    {
        self::headerResponseCode(403, "forbidden");
        self::errorLog("forbidden", $message);
        self::die($message);
    }

    public static function arrayGetOrBadRequest(array $array, ...$keys)
    {
        $cursor = $array;
        $keysTraversed = [];
        foreach ($keys as $key) {
            array_push($keysTraversed, $key);
            if (!isset($cursor[$key])) {
                self::badRequest("array key not found: " . json_encode($keysTraversed));
            }
            $cursor = $cursor[$key];
        }
        return $cursor;
    }

    public static function alert(string $message)
    {
        // json_encode escapes quotes
        echo "<script type='text/javascript'>alert(" . json_encode($message) . ");</script>";
    }

    public static function testValidSSHKey($key_str)
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
}
