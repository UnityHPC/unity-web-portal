<?php

namespace UnityWebPortal\lib;

use phpseclib3\Crypt\PublicKeyLoader;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;

class UnitySite
{
    public static function die($x)
    {
        if (@$GLOBALS["PHPUNIT_NO_DIE_PLEASE"] == true) {
            throw new PhpUnitNoDieException(strval($x));
        } else {
            \die($x);
        }
    }

    public static function redirect($destination)
    {
        if ($_SERVER["PHP_SELF"] != $destination) {
            header("Location: $destination");
            self::die("Redirect failed, click <a href='$destination'>here</a> to continue.");
        }
    }

    public static function headerResponseCode(int $code)
    {
        $responseCodeMessage = @http_response_code($code) ?? "";
        $msg = $_SERVER["SERVER_PROTOCOL"] . " " . strval($code) . " " . $responseCodeMessage;
        header($msg, true, $ncode);
    }

    public static function errorLog(string $title, string $message)
    {
        error_log(
            "$title: " . json_encode(
                [
                    "message" => $message,
                    "REMOTE_USER" => @$_SERVER["REMOTE_USER"], // "@": allow null default value
                    "REMOTE_ADDR" => @$_SERVER["REMOTE_ADDR"], // "@": allow null default value
                    // getTrace() is a list but the JSON is very verbose
                    "trace" => explode(PHP_EOL, (new \Exception())->getTraceAsString())
                ]
            )
        );
    }

    public static function badRequest($message)
    {
        self::headerResponseCode(400);
        self::errorLog("bad request", $message);
        self::die($message);
    }

    public static function removeTrailingWhitespace($arr)
    {
        $out = array();
        foreach ($arr as $str) {
            $new_string = rtrim($str);
            array_push($out, $new_string);
        }

        return $out;
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
