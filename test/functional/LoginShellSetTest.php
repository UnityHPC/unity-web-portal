<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LoginShellSetTest extends TestCase
{
    private static $_initialLoginShell;

    public static function setUpBeforeClass(): void
    {
        global $USER;
        switchUser(...getNormalUser());
        self::$_initialLoginShell = $USER->getLoginShell();
    }

    public function tearDown(): void
    {
        global $USER;
        $USER->setLoginShell(self::$_initialLoginShell);
    }

    public static function getShells()
    {
        global $HTTP_HEADER_TEST_INPUTS;
        // phpcs:disable
        return [["/bin/bash"]] + array_map(function($x){return [$x];}, $HTTP_HEADER_TEST_INPUTS);
        // phpcs:enable
    }

    #[DataProvider("getShells")]
    public function testSetLoginShellCustom(string $shell): void
    {
        global $USER;
        // FIXME add check to avoid warning from ldap_modify
        if (!mb_check_encoding($shell, 'ASCII')) {
            $this->expectException("Exception");
        }
        if ($shell != trim($shell)) {
            $this->expectException("Exception");
        }
        // FIXME shell is not validated
        post(
            __DIR__ . "/../../webroot/panel/account.php",
            ["form_type" => "loginshell", "shellSelect" => "Custom", "shell" => $shell]
        );
        $this->assertEquals($shell, $USER->getLoginShell());
    }

    #[DataProvider("getShells")]
    public function testSetLoginShellSelect(string $shell): void
    {
        global $USER;
        // FIXME add check to avoid warning from ldap_modify
        if (!mb_check_encoding($shell, 'ASCII')) {
            $this->expectException("Exception");
        }
        // FIXME shell is not validated
        post(
            __DIR__ . "/../../webroot/panel/account.php",
            ["form_type" => "loginshell", "shellSelect" => $shell]
        );
        $this->assertEquals($shell, $USER->getLoginShell());
    }
}
