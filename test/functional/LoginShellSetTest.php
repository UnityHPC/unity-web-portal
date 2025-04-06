<?php

namespace UnityWebPortal\lib;

// use Mockery;
// use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LoginShellSetTest extends TestCase {
    private static $initial_shell;
    private static $test_inputs;

    public static function setUpBeforeClass(): void{
        global $USER;
        self::$initial_shell = $USER->getLoginShell();

    }

    public function setUp(): void {
        ob_start();
    }

    public function tearDown(): void {
        global $USER;
        $USER->setLoginShell(self::$initial_shell);
        ob_get_clean();
    }

    // https://stackoverflow.com/a/16853473/18696276
    private static function contains_any_multibyte($string){
        return !mb_check_encoding($string, 'ASCII') && mb_check_encoding($string, 'UTF-8');
    }

    private function set_login_shell_custom(string $shell): void {
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "loginshell", "shellSelect" => "custom", "shell" => $shell]
        );
    }

    private function set_login_shell_select(string $shell): void {
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "loginshell", "shellSelect" => $shell]
        );
    }

    public static function shell_provider() {
        global $HTTP_HEADER_TEST_INPUTS;
        return [["/bin/bash"]] + array_map(function($x){return [$x];}, $HTTP_HEADER_TEST_INPUTS);
    }

    #[DataProvider("shell_provider")]
    public function test_set_login_shell_custom(string $shell): void {
        global $USER;
        if (self::contains_any_multibyte($shell)) {
            $this->expectException("Exception");
        }
        // FIXME shell is not validated
        $this->set_login_shell_custom($shell);
        $this->assertEquals($shell, $USER->getLoginShell());
    }

    #[DataProvider("shell_provider")]
    public function test_set_login_shell_select(string $shell): void {
        global $USER;
        if (self::contains_any_multibyte($shell)) {
            $this->expectException("Exception");
        }
        // FIXME shell is not validated
        $this->set_login_shell_select($shell);
        $this->assertEquals($shell, $USER->getLoginShell());
    }
}
