<?php
namespace UnityWebPortal\lib;
class CSRFToken
{
    private const SESSION_KEY = "csrf_token";
    public const PARAMETER_NAME = "csrf_token";
    public static function generate(): string
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException("Session is not started. Call session_start() first.");
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;
        return $token;
    }

    public static function getToken(): string
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException("Session is not started. Call session_start() first.");
        }
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return self::generate();
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token = null): bool
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException("Session is not started. Call session_start() first.");
        }
        if ($token === null) {
            $token = $_POST[self::PARAMETER_NAME] ?? ($_GET[self::PARAMETER_NAME] ?? null);
        }
        if ($token === null || $token === "") {
            return false;
        }
        $storedToken = $_SESSION[self::SESSION_KEY] ?? null;
        if ($storedToken === null || $storedToken === "") {
            return false;
        }
        return hash_equals($storedToken, $token);
    }

    public static function getHiddenInput(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, "UTF-8");
        $paramName = htmlspecialchars(self::PARAMETER_NAME, ENT_QUOTES, "UTF-8");
        return "<input type='hidden' name='$paramName' value='$token'>";
    }

    public static function clear(): void
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }
}
