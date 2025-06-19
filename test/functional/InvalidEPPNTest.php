<?php

use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InvalidEPPNTest extends TestCase
{
    public static function provider()
    {
        return [
            ["",      false],
            ["a",     false],
            ["a@b",   true],
            ["a@b@c", false],
        ];
    }

    #[DataProvider("provider")]
    public function testInitGetSSO(string $eppn, bool $is_valid): void
    {
        global $SSO;
        $original_server = $_SERVER;
        $original_sso = $SSO;
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_write_close();
            session_id(uniqid());
        }
        if (!$is_valid) {
            $this->expectException(PhpUnitNoDieException::class);
            $this->expectExceptionMessageMatches("/.*Invalid eppn.*/");
        }
        try {
            $_SERVER["REMOTE_USER"] = $eppn;
            $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
            $_SERVER["eppn"] = $eppn;
            $_SERVER["givenName"] = "foo";
            $_SERVER["sn"] = "bar";
            // can't use http_get because it does `require_once`
            // won't use phpunit --process-isolation because when I try that argument all tests fail with a blank error message
            include __DIR__ . "/../../resources/init.php";
        } finally {
            $_SERVER = $original_server;
            $SSO = $original_sso;
        }
        $this->assertTrue(true); // if $is_valid, there are no other assertions
    }
}
