<?php
namespace UnityWebPortal\lib;
class CSRFToken
{
    public static function generate(): string
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException("Session is not started. Call session_start() first.");
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION["csrf_token"] = $token;
        return $token;
    }

    public static function getToken(): string
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException("Session is not started. Call session_start() first.");
        }
        if (!isset($_SESSION["csrf_token"])) {
            return self::generate();
        }
        return $_SESSION["csrf_token"];
    }

    public static function validate(?string $token = null): bool
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException("Session is not started. Call session_start() first.");
        }
        if ($token === null) {
            $token = $_POST["csrf_token"] ?? ($_GET["csrf_token"] ?? null);
        }
        if ($token === null || $token === "") {
            return false;
        }
        $storedToken = $_SESSION["csrf_token"] ?? null;
        if ($storedToken === null || $storedToken === "") {
            return false;
        }
        return hash_equals($storedToken, $token);
    }

    public static function getHiddenInput(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, "UTF-8");
        $paramName = htmlspecialchars("csrf_token", ENT_QUOTES, "UTF-8");
        return "<input type='hidden' name='$paramName' value='$token'>";
    }

    public static function clear(): void
    {
        if (isset($_SESSION["csrf_token"])) {
            unset($_SESSION["csrf_token"]);
        }
    }
}
