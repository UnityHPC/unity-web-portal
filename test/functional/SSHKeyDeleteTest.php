<?php

use PHPUnit\Framework\Attributes\DataProvider;
use TRegx\PhpUnit\DataProviders\DataProvider as TRegxDataProvider;
use UnityWebPortal\lib\exceptions\ArrayKeyException;

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

    private function deleteKey(string $key): void
    {
        http_post(__DIR__ . "/../../webroot/panel/account.php", [
            "form_type" => "delKey",
            "delKey" => $key,
        ]);
    }

    public static function getGarbageKeys()
    {
        global $HTTP_HEADER_TEST_INPUTS;
        return array_map(fn($x) => [$x], $HTTP_HEADER_TEST_INPUTS);
    }

    #[DataProvider("getGarbageKeys")]
    public function testDeleteKeyGarbageInput(string $key)
    {
        global $USER;
        try {
            $this->expectException(ArrayKeyException::class);
            $this->deleteKey($key);
            $this->assertEquals(self::$initialKeys, $USER->getSSHKeys());
        } finally {
            callPrivateMethod($USER, "setSSHKeys", self::$initialKeys);
        }
    }

    public function testDeleteKey()
    {
        global $USER;
        try {
            $key = self::$initialKeys[0];
            $this->assertNotNull($key);
            $this->deleteKey($key);
            $this->assertEquals([], $USER->getSSHKeys());
        } finally {
            callPrivateMethod($USER, "setSSHKeys", self::$initialKeys);
        }
    }
}
