<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AjaxSshValidateTest extends TestCase
{
    public static function providerTestSshValidate()
    {
        // sanity check only, see UnitySiteTest for more comprehensive test cases
        return [
            [false, "foobar"],
            // phpcs:disable
            [true, "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIB+XqO25MUB9x/pS04I3JQ7rMGboWyGXh0GUzkOrTi7a"],
            // phpcs:enable
        ];
    }

    #[DataProvider("providerTestSshValidate")]
    public function testSshValidate(bool $is_valid, string $pubkey)
    {
        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["key"] = $pubkey;
        ob_start();
        include __DIR__ . "/../../webroot/js/ajax/ssh_validate.php";
        $output = ob_get_clean();
        if ($is_valid) {
            $this->assertEquals("true", $output);
        } else {
            $this->assertEquals("false", $output);
        }
    }
}
