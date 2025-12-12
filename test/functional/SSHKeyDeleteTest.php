<?php

use PHPUnit\Framework\Attributes\DataProvider;

class SSHKeyDeleteTest extends UnityWebPortalTestCase
{
    static $initialKeys;

    public static function setUpBeforeClass(): void
    {
        global $USER;
        switchUser(...getUserWithOneKey());
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
        return array_map(function ($x) {
            return [$x];
        }, $HTTP_HEADER_TEST_INPUTS);
    }

    #[DataProvider("getGarbageIndexArgs")]
    public function testDeleteKeyGarbageInput(string $index)
    {
        global $USER;
        try {
            $this->deleteKey($index);
            $this->assertEquals(self::$initialKeys, $USER->getSSHKeys());
        } finally {
            $USER->setSSHKeys(self::$initialKeys);
        }
    }

    public function testDeleteKeyNegativeIndex()
    {
        global $USER;
        try {
            $this->deleteKey("-1");
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

    public function testDeleteKeyDecimal()
    {
        global $USER;
        try {
            $this->deleteKey("0.5");
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
