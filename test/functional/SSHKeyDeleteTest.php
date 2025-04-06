<?php

namespace UnityWebPortal\lib;

use Mockery;
use Exception;
use PHPUnit\Framework\TestCase;

class SSHKeyDeleteTest extends TestCase {
    private static $initial_keys;

    public static function setUpBeforeClass(): void{
        global $USER;
        self::$initial_keys = $USER->getSSHKeys();
    }

    private function delete_ssh_key(string $index): void {
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "delKey", "delIndex" => $index]
        );
    }

    private function test_delete_ssh_key(string $index): void {
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

    protected function setUp(): void {
        // expectException prevents ob_get_clean from running
        ob_start();
    }

    protected function tearDown(): void {
        global $USER;
        ob_get_clean();
        $USER->setSSHKeys(self::$initial_keys);
    }

    public function test_delete_negative1() {
        $this->test_delete_ssh_key("-1");
    }

    public function test_delete_0() {
        $this->test_delete_ssh_key("0");
    }

    public function test_delete_too_big() {
        global $CONFIG;
        $max = intval($CONFIG["ldap"]["max_num_ssh_keys"]);
        $this->test_delete_ssh_key((string)($max + 1));
    }

    public function test_delete_non_number() {
        $this->test_delete_ssh_key("foobar");
    }
}
