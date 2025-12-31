<?php

use UnityWebPortal\lib\UnityGithub;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class SSHKeyAddTest extends UnityWebPortalTestCase
{
    private function addSshKeysPaste(array $keys): void
    {
        foreach ($keys as $key) {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "addKey",
                "add_type" => "paste",
                "key" => $key,
            ]);
        }
    }

    private function addSshKeysImport(array $keys): void
    {
        foreach ($keys as $key) {
            $tmp = tmpfile();
            $tmp_path = stream_get_meta_data($tmp)["uri"];
            fwrite($tmp, $key);
            $_FILES["keyfile"] = ["tmp_name" => $tmp_path];
            try {
                http_post(__DIR__ . "/../../webroot/panel/account.php", [
                    "form_type" => "addKey",
                    "add_type" => "import",
                ]);
                $this->assertFalse(file_exists($tmp_path));
            } finally {
                unset($_FILES["keyfile"]);
            }
        }
    }

    private function addSshKeysGenerate(array $keys): void
    {
        foreach ($keys as $key) {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "addKey",
                "add_type" => "generate",
                "gen_key" => $key,
            ]);
        }
    }

    private function addSshKeysGithub(array $keys): void
    {
        global $GITHUB;
        $oldGithub = $GITHUB;
        $GITHUB = $this->createMock(UnityGithub::class);
        $GITHUB->method("getSshPublicKeys")->willReturn($keys);
        try {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "addKey",
                "add_type" => "github",
                "gh_user" => "foobar",
            ]);
        } finally {
            $GITHUB = $oldGithub;
        }
    }

    public static function provider()
    {
        $validKey =
            "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIB+XqO25MUB9x/pS04I3JQ7rMGboWyGXh0GUzkOrTi7a foobar";
        $invalidKey = "foobar";
        $methods = [
            "addSshKeysPaste",
            "addSshKeysImport",
            "addSshKeysGenerate",
            "addSshKeysGithub",
        ];
        $output = [];
        foreach ($methods as $method) {
            $output = array_merge($output, [
                [$method, 0, []],
                [$method, 0, [$invalidKey]],
                [$method, 1, [$validKey]],
                [$method, 1, [$validKey, $invalidKey]],
                [$method, 1, [$validKey, $validKey]],
            ]);
        }
        return $output;
    }

    public function getKeyCount()
    {
        global $USER;
        return count($USER->getSSHKeys());
    }

    #[AllowMockObjectsWithoutExpectations]
    #[DataProvider("provider")]
    public function testAddSshKeys(string $methodName, int $expectedKeysAdded, array $keys)
    {
        global $USER;
        $this->switchUser("HasNoSshKeys");
        $numKeysBefore = $this->getKeyCount();
        $this->assertEquals(0, $numKeysBefore);
        try {
            call_user_func([SSHKeyAddTest::class, $methodName], $keys);
            // $method($keys);
            $numKeysAfter = $this->getKeyCount();
            $this->assertEquals($expectedKeysAdded, $numKeysAfter - $numKeysBefore);
        } finally {
            $USER->setSSHKeys([]);
        }
    }
}
