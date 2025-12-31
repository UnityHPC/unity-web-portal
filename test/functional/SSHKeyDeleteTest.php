<?php

use PHPUnit\Framework\Attributes\DataProvider;

class SSHKeyDeleteTest extends UnityWebPortalTestCase
{
    static $initialKeys;

    public function setUp(): void
    {
        parent::setUp();
        global $USER;
        $this->switchUser("WithOneKey");
        self::$initialKeys = $USER->getSSHKeys();
    }

    private function deleteKey(string $index): void
    {
        http_post(__DIR__ . "/../../webroot/panel/account.php", [
            "form_type" => "delKey",
            "delIndex" => $index,
        ]);
    }

    public static function getGarbageIndexArgs()
    {
        global $HTTP_HEADER_TEST_INPUTS;
        $http_header_test_inputs_no_ints = array_filter(
            $HTTP_HEADER_TEST_INPUTS,
            fn($x) => !ctype_digit($x),
        );
        $http_header_test_inputs_no_ints_2d = array_map(
            fn($x) => [$x],
            $http_header_test_inputs_no_ints,
        );
        return array_merge([["-1"], ["0.5"]], $http_header_test_inputs_no_ints_2d);
    }

    #[DataProvider("getGarbageIndexArgs")]
    public function testDeleteKeyGarbageInput(string $index)
    {
        global $USER;
        try {
            $this->expectException(ValueError::class);
            $this->deleteKey($index);
            $this->assertEquals(self::$initialKeys, $USER->getSSHKeys());
        } finally {
            $USER->setSSHKeys(self::$initialKeys);
        }
    }

    public function testDeleteKeyIndexTooLarge()
    {
        global $USER;
        try {
            $this->deleteKey("99");
            $this->assertEquals(self::$initialKeys, $USER->getSSHKeys());
        } finally {
            $USER->setSSHKeys(self::$initialKeys);
        }
    }

    public function testDeleteKey()
    {
        global $USER;
        try {
            $this->deleteKey("0");
            $this->assertEquals([], $USER->getSSHKeys());
        } finally {
            $USER->setSSHKeys(self::$initialKeys);
        }
    }
}
