<?php

use PHPUnit\Framework\Attributes\DataProvider;
use TRegx\PhpUnit\DataProviders\DataProvider as TRegxDataProvider;

class SSHKeyDeleteTest extends UnityWebPortalTestCase
{
    static $initialKeys;

    public function setUp(): void
    {
        parent::setUp();
        global $USER;
        $this->switchUser("HasOneSshKey");
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
        return TRegxDataProvider::list("-1", "0.5", ...$http_header_test_inputs_no_ints);
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
