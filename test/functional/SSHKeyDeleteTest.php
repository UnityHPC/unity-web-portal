<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SSHKeyDeleteTest extends TestCase {
    private static $initial_keys;

    public static function setUpBeforeClass(): void{
        global $USER;
        self::$initial_keys = $USER->getSSHKeys();
    }

    protected function setUp(): void {
        // expectException prevents ob_get_clean from running
        ob_start();
    }

    protected function tearDown(): void {
        global $USER;
        ob_get_clean();
        $USER->setSSHKeys(self::$initial_keys);
    }

    private function delete_ssh_key(string $index): void {
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "delKey", "delIndex" => $index]
        );
    }

    public static function index_provider(){
        global $CONFIG, $HTTP_HEADER_TEST_INPUTS;
        $max = intval($CONFIG["ldap"]["max_num_ssh_keys"]);
        return [
            ["-1"],
            ["0"],
            [(string)($max + 1)]
        ] + array_map(function($x){return [$x];}, $HTTP_HEADER_TEST_INPUTS);
    }

    #[DataProvider("index_provider")]
    public function test_delete_ssh_key(string $index): void {
        // take into account whether index is valid
        // at the end, return keys to their initial state
        global $USER, $CONFIG, $SITE;
        $keys_before = $USER->getSSHKeys();
        $expect_bad_request_exception = false;
        if (!preg_match("/^[0-9]+$/", $index)) {
            $expected_keys_after = $keys_before;
            $this->expectException(BadRequestException::class);
        }
        $index_int = intval($index);
        if ($index_int >= count($keys_before)){
            $expected_keys_after = $keys_before;
            $this->expectException(BadRequestException::class);
        } else {
            $expected_keys_after = $keys_before;
            unset($expected_keys_after[$index_int]);
        }
        $this->delete_ssh_key($index);
        $keys_after = $USER->getSSHKeys();
        $this->assertEquals(array_values($expected_keys_after), array_values($keys_after));
    }
}
