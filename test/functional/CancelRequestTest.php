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
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/new_account.php",
                ["new_user_sel" => "pi", "eula" => "agree", "confirm_pi" => "agree"]
            );
        } catch (PhpUnitNoDieException $e) {
            // Ignore the exception from http_post
        }

        $this->assertNumberGroupRequests(1);

        // Now try to cancel it
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/new_account.php",
                ["cancel" => "agree"]
            );
        } catch (PhpUnitNoDieException $e) {
            // Ignore the exception from http_post
        }

        $this->assertNumberGroupRequests(0);
    }

    public function testCancelGroupJoinRequest()
    {
        global $USER, $SQL;
        switchUser(...getNonExistentUser());

        try {
            http_post(
                __DIR__ . "/../../webroot/panel/new_account.php",
                ["new_user_sel" => "not_pi", "eula" => "agree", "pi" => getExistingPI()]
            );
        } catch (PhpUnitNoDieException $e) {
            // Ignore the exception from http_post
        }

        $this->assertNumberGroupRequests(1);

        // Now try to cancel it
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/new_account.php",
                ["cancel" => "agree"]
            );
        } catch (PhpUnitNoDieException $e) {
            // Ignore the exception from http_post
        }

        $this->assertNumberGroupRequests(0);
    }
}
