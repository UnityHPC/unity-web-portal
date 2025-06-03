<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;

class CancelRequestTest extends TestCase
{
    private function assertNumberGroupRequests(int $x)
    {
        global $USER, $SQL;
        $this->assertEquals($x, count($SQL->getRequestsByUser($USER->getUID())));
    }

    public function testCancelPIRequest()
    {
        global $USER, $SQL;
        switchUser(...getNonExistentUser());
        // First create a request
        http_post(
            __DIR__ . "/../../webroot/panel/new_account.php",
            ["new_user_sel" => "pi", "eula" => "agree", "confirm_pi" => "agree"]
        );

        $this->assertNumberGroupRequests(1);

        // Now try to cancel it
        http_post(
            __DIR__ . "/../../webroot/panel/new_account.php",
            ["cancel" => "true"] # value of cancel is arbitrary
        );

        $this->assertNumberGroupRequests(0);
    }

    public function testCancelGroupJoinRequest()
    {
        global $USER, $SQL;
        switchUser(...getNonExistentUser());

        http_post(
            __DIR__ . "/../../webroot/panel/new_account.php",
            ["new_user_sel" => "not_pi", "eula" => "agree", "pi" => getExistingPI()]
        );

        $this->assertNumberGroupRequests(1);

        // Now try to cancel it
        http_post(
            __DIR__ . "/../../webroot/panel/new_account.php",
            ["cancel" => "true"] # value of cancel is arbitrary
        );

        $this->assertNumberGroupRequests(0);
    }
}
