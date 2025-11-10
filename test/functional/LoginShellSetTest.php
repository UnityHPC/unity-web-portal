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
        return [["/bin/bash"]] +
            array_map(function ($x) {
                return [$x];
            }, $HTTP_HEADER_TEST_INPUTS);
    }

    private function isShellValid(string $shell)
    {
        return mb_check_encoding($shell, "ASCII") && $shell == trim($shell) && !empty($shell);
    }

    #[DataProvider("getShells")]
    public function testSetLoginShell(string $shell): void
    {
        global $USER;
        if (!$this->isShellValid($shell)) {
            $this->expectException("Exception");
        }
        http_post(__DIR__ . "/../../webroot/panel/account.php", [
            "form_type" => "loginshell",
            "shellSelect" => $shell,
        ]);
        $this->assertEquals($shell, $USER->getLoginShell());
    }
}
