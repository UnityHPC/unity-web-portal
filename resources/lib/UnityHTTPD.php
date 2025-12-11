<?php

namespace UnityWebPortal\lib;

use UnityWebPortal\lib\exceptions\NoDieException;
use UnityWebPortal\lib\exceptions\ArrayKeyException;
use RuntimeException;

enum UnityHTTPDMessageLevel: string
{
    case DEBUG = "debug";
    case INFO = "info";
    case SUCCESS = "success";
    case WARNING = "warning";
    case ERROR = "error";
}

class UnityHTTPD
{
    public static function die(mixed $x = null, bool $show_user = false): never
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

    public static function redirect(?string $dest = null): never
    {
        $dest ??= pathJoin(CONFIG["site"]["prefix"], $_SERVER["REQUEST_URI"]);
        $dest = htmlspecialchars($dest);
        header("Location: $dest");
        self::errorToUser("Redirect failed, click <a href='$dest'>here</a> to continue.", 302);
        self::die();
    }

    // $data must be JSON serializable
    public static function errorLog(
        string $title,
        string $message,
        ?string $errorid = null,
        ?\Throwable $error = null,
        mixed $data = null,
    ): void {
        if (!CONFIG["site"]["enable_verbose_error_log"]) {
            error_log("$title: $message");
            return;
        }
        $output = ["message" => $message];
        if (!is_null($data)) {
            try {
                \jsonEncode($data);
                $output["data"] = $data;
            } catch (\JsonException $e) {
                $output["data"] = "data could not be JSON encoded: " . $e->getMessage();
            }
        }
        if (!is_null($error)) {
            $output["error"] = self::throwableToArray($error);
        } else {
            // newlines are bad for error log, but getTrace() is too verbose
            $output["trace"] = explode("\n", (new \Exception())->getTraceAsString());
        }
        $output["REMOTE_USER"] = $_SERVER["REMOTE_USER"] ?? null;
        $output["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"] ?? null;
        if (!is_null($errorid)) {
            $output["errorid"] = $errorid;
        }
        error_log("$title: " . \jsonEncode($output));
    }

    // recursive on $t->getPrevious()
    private static function throwableToArray(\Throwable $t): array
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
        ?string $errorid = null,
    ): void {
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

    public static function badRequest(
        string $message,
        ?\Throwable $error = null,
        ?array $data = null,
    ): never {
        $errorid = uniqid();
        self::errorToUser("Invalid requested action or submitted data.", 400, $errorid);
        self::errorLog("bad request", $message, $errorid, $error, $data);
        self::die($message);
    }

    public static function forbidden(
        string $message,
        ?\Throwable $error = null,
        ?array $data = null,
    ): never {
        $errorid = uniqid();
        self::errorToUser("Permission denied.", 403, $errorid);
        self::errorLog("forbidden", $message, $errorid, $error, $data);
        self::die($message);
    }

    public static function internalServerError(
        string $message,
        ?\Throwable $error = null,
        ?array $data = null,
    ): never {
        $errorid = uniqid();
        self::errorToUser("An internal server error has occurred.", 500, $errorid);
        self::errorLog("internal server error", $message, $errorid, $error, $data);
        if (!is_null($error) && ini_get("display_errors") && ini_get("html_errors")) {
            echo "<table>";
            echo $error->xdebug_message;
            echo "</table>";
        }
        self::die($message);
    }

    // https://www.php.net/manual/en/function.set-exception-handler.php
    public static function exceptionHandler(\Throwable $e): void
    {
        ini_set("log_errors", true); // in case something goes wrong and error is not logged
        self::internalServerError("An internal server error has occurred.", error: $e);
    }

    public static function errorHandler(int $severity, string $message, string $file, int $line)
    {
        if (str_contains($message, "Undefined array key")) {
            throw new ArrayKeyException($message);
        }
        return false;
    }

    public static function getPostData(string $key): mixed
    {
        try {
            return $_POST[$key];
        } catch (ArrayKeyException $e) {
            self::badRequest('failed to get $_POST data', $e, [
                '$_POST' => $_POST,
            ]);
        }
    }

    public static function getUploadedFileContents(
        string $filename,
        bool $do_delete_tmpfile_after_read = true,
        string $encoding = "UTF-8",
    ): string {
        try {
            $tmpfile_path = $_FILES[$filename]["tmp_name"];
        } catch (ArrayKeyException $e) {
            self::badRequest("no such uploaded file", $e, [
                '$_FILES' => $_FILES,
            ]);
        }
        $contents = file_get_contents($tmpfile_path);
        if ($contents === false) {
            throw new \Exception("Failed to read file: " . $tmpfile_path);
        }
        if ($do_delete_tmpfile_after_read) {
            unlink($tmpfile_path);
        }
        $old_encoding = mbDetectEncoding($contents);
        return mbConvertEncoding($contents, $encoding, $old_encoding);
    }

    // in firefox, the user can disable alert/confirm/prompt after the 2nd or 3rd popup
    // after I disable alerts, if I quit and reopen my browser, the alerts come back
    public static function alert(string $message): void
    {
        // jsonEncode escapes quotes
        echo "<script type='text/javascript'>alert(" . \jsonEncode($message) . ");</script>";
    }

    private static function ensureSessionMessagesSanity()
    {
        if (!isset($_SESSION)) {
            throw new RuntimeException('$_SESSION is unset');
        }
        if (!array_key_exists("messages", $_SESSION)) {
            self::errorLog(
                "invalid session messages",
                'array key "messages" does not exist for $_SESSION',
                data: ['$_SESSION' => $_SESSION],
            );
            $_SESSION["messages"] = [];
        }
        if (!is_array($_SESSION["messages"])) {
            $type = gettype($_SESSION["messages"]);
            self::errorLog(
                "invalid session messages",
                "\$_SESSION['messages'] is type '$type', not an array",
                data: ['$_SESSION' => $_SESSION],
            );
            $_SESSION["messages"] = [];
        }
    }

    public static function message(string $title, string $body, UnityHTTPDMessageLevel $level)
    {
        self::ensureSessionMessagesSanity();
        array_push($_SESSION["messages"], [$title, $body, $level]);
    }

    public static function messageDebug(string $title, string $body)
    {
        return self::message($title, $body, UnityHTTPDMessageLevel::DEBUG);
    }
    public static function messageInfo(string $title, string $body)
    {
        return self::message($title, $body, UnityHTTPDMessageLevel::INFO);
    }
    public static function messageSuccess(string $title, string $body)
    {
        return self::message($title, $body, UnityHTTPDMessageLevel::SUCCESS);
    }
    public static function messageWarning(string $title, string $body)
    {
        return self::message($title, $body, UnityHTTPDMessageLevel::WARNING);
    }
    public static function messageError(string $title, string $body)
    {
        return self::message($title, $body, UnityHTTPDMessageLevel::ERROR);
    }

    public static function getMessages()
    {
        self::ensureSessionMessagesSanity();
        return $_SESSION["messages"];
    }

    public static function clearMessages()
    {
        $_SESSION["messages"] = [];
    }
}
