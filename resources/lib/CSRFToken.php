<?php
namespace UnityWebPortal\lib;

class CSRFToken
{
    private static ?string $requestToken = null;

    private static function ensureSession(): void
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException("Session is not started. Call session_start() first.");
        }
        if (!array_key_exists("csrf_tokens", $_SESSION) || !is_array($_SESSION["csrf_tokens"])) {
            $_SESSION["csrf_tokens"] = [];
        }
    }

    public static function generate(): string
    {
        self::ensureSession();
        $token = bin2hex(random_bytes(32));
        $_SESSION["csrf_tokens"][$token] = false;
        return $token;
    }

    public static function getToken(): string
    {
        self::ensureSession();
        if (self::$requestToken !== null) {
            return self::$requestToken;
        }
        $token = self::generate();
        self::$requestToken = $token;
        return $token;
    }

    public static function validate(string $token): bool
    {
        self::ensureSession();
        if ($token === "") {
            UnityHTTPD::errorLog("empty CSRF token", "");
            return false;
        }
        if (!array_key_exists($token, $_SESSION["csrf_tokens"])) {
            UnityHTTPD::errorLog("unknown CSRF token", $token);
            return false;
        }
        $entry = $_SESSION["csrf_tokens"][$token];
        if ($entry === true) {
            UnityHTTPD::errorLog("reused CSRF token", $token);
            return false;
        }
        $_SESSION["csrf_tokens"][$token] = true;
        self::$requestToken = null;
        return true;
    }

    public static function getHiddenInput(): string
    {
        $token = htmlspecialchars(self::getToken());
        return "<input type='hidden' name='csrf_token' value='$token'>";
    }

    public static function clear(): void
    {
        if (!isset($_SESSION)) {
            return;
        }
        if (array_key_exists("csrf_tokens", $_SESSION)) {
            unset($_SESSION["csrf_tokens"]);
        }
        if (array_key_exists("csrf_token", $_SESSION)) {
            unset($_SESSION["csrf_token"]);
        }
        self::$requestToken = null;
    }
}
