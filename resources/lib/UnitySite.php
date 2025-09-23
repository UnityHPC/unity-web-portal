<?php

namespace UnityWebPortal\lib;

use phpseclib3\Crypt\PublicKeyLoader;
use UnityWebPortal\lib\exceptions\NoDieException;
use UnityWebPortal\lib\exceptions\ArrayKeyException;

class UnitySite
{
    public static function die($x = null, $show_user = false)
    {
        if (CONFIG["site"]["allow_die"] == false) {
            if (is_null($x)) {
                throw new NoDieException();
            } else {
                throw new NoDieException($x);
            }
        } else {
            if (!is_null($x) and $show_user) {
                die($x);
            } else {
                die();
            }
        }
    }

    public static function redirect($dest)
    {
        header("Location: $dest");
        self::errorToUser("Redirect failed, click <a href='$dest'>here</a> to continue.", 302);
        self::die();
    }

    // $data must be JSON serializable
    public static function errorLog(
        string $title,
        string $message,
        string|null $errorid = null,
        Throwable|null $error = null,
        mixed $data = null,
    ) {
        if (!CONFIG["site"]["enable_verbose_error_log"]) {
            error_log("$title: $message");
            return;
        }
        $output = [
            "message" => $message,
            "REMOTE_USER" => $_SERVER["REMOTE_USER"] ?? null,
            "REMOTE_ADDR" => $_SERVER["REMOTE_ADDR"] ?? null,
        ];
        if (!is_null($errorid)) {
            $output["errorid"] = $errorid;
        }
        if (!is_null($error)) {
            $output["error"] = self::throwableToArray($error);
        } else {
            // newlines are bad for error log, but getTrace() is too verbose
            $output["trace"] = explode("\n", (new \Exception())->getTraceAsString());
        }
        if (!is_null($data)) {
            $output["data"] = $data;
        }
        error_log("$title: " . json_encode($output, JSON_UNESCAPED_SLASHES));
    }

    // recursive on $t->getPrevious()
    private static function throwableToArray(Throwable $t): array
    {
        $output = [
            "type" => gettype($t),
            "msg" => $t->getMessage(),
            // newlines are bad for error log, but getTrace() is too verbose
            "trace" => explode("\n", $t->getTraceAsString()),
        ];
        $previous = $t->getPrevious();
        if (!is_null($previous)) {
            $output["previous"] = self::throwableToArray($previous);
        }
        return $output;
    }

    private static function errorToUser(
        string $msg,
        int $http_response_code,
        string|null $errorid = null
    ) {
        if (!CONFIG["site"]["enable_error_to_user"]) {
            return;
        }
        $notes = "Please notify a Unity admin at " . CONFIG["mail"]["support"] . ".";
        if (!is_null($errorid)) {
            $notes = $notes . " Error ID: $errorid.";
        }
        if (!headers_sent()) {
            http_response_code($http_response_code);
        }
        // text may not be shown in the webpage in an obvious way, so make a popup
        self::alert("$msg $notes");
        echo "<h1>$msg</h1><p>$notes</p>";
    }

    public static function badRequest($message, $error = null, $data = null)
    {
        $errorid = uniqid();
        self::errorToUser("Invalid requested action or submitted data.", 400, $errorid);
        self::errorLog("bad request", $message, $errorid, $error, $data);
        self::die($message);
    }

    public static function forbidden($message, $error = null, $data = null)
    {
        $errorid = uniqid();
        self::errorToUser("Permission denied.", 403, $errorid);
        self::errorLog("forbidden", $message, $errorid, $error, $data);
        self::die($message);
    }

    public static function internalServerError($message, $error = null, $data = null)
    {
        $errorid = uniqid();
        self::errorToUser("An internal server error has occurred.", 500, $errorid);
        self::errorLog("internal server error", $message, $errorid, $error, $data);
        self::die($message);
    }

    // https://www.php.net/manual/en/function.register-shutdown-function.php
    public static function shutdown()
    {
        $e = error_get_last();
        if (is_null($e) || $e["type"] !== E_ERROR) {
            return;
        }
        // newlines are bad for error log
        if (!is_null($e) && array_key_exists("message", $e) && str_contains($e["message"], "\n")) {
            $e["message"] = explode("\n", $e["message"]);
        }
        // error_get_last is an array, not a Throwable
        self::internalServerError("An internal server error has occurred.", data: ["error" => $e]);
    }

    public static function getPostData(...$keys)
    {
        try {
            return \arrayGet($_POST, ...$keys);
        } catch (ArrayKeyException $e) {
            self::badRequest('failed to get $_POST data', $e, ['$_POST' => $_POST]);
        }
    }

    public static function getUploadedFileContents($filename, $do_delete_tmpfile_after_read = true)
    {
        try {
            $tmpfile_path = \arrayGet($_FILES, $filename, "tmp_name");
        } catch (ArrayKeyException $e) {
            self::badRequest('no such uploaded file', $e, ['$_FILES' => $_FILES]);
        }
        $contents = file_get_contents($tmpfile_path);
        if ($contents === false) {
            throw new \Exception("Failed to read file: " . $tmpfile_path);
        }
        if ($do_delete_tmpfile_after_read) {
            unlink($tmpfile_path);
        }
        return $contents;
    }

    // in firefox, the user can disable alert/confirm/prompt after the 2nd or 3rd popup
    // after I disable alerts, if I quit and reopen my browser, the alerts come back
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
