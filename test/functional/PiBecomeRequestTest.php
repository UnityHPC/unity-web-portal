<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\UnitySQL;

class PIBecomeRequestTest extends TestCase
{
    public function testRequestBecomePi()
    {
        global $USER, $SQL;
        switchUser(...getBlankUser());
        $this->assertFalse($USER->isPI());
        $this->assertNumberPiBecomeRequests(0);
        try {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "pi_request",
                "tos" => "agree",
                "account_policy" => "agree",
            ]);
            $this->assertNumberPiBecomeRequests(1);
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "cancel_pi_request",
            ]);
            $this->assertNumberPiBecomeRequests(0);
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "pi_request",
                "tos" => "agree",
                "account_policy" => "agree",
            ]);
            $this->assertNumberPiBecomeRequests(1);
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "pi_request",
                "tos" => "agree",
                "account_policy" => "agree",
            ]);
            $this->assertNumberPiBecomeRequests(1);
        } finally {
            if ($SQL->requestExists($USER, UnitySQL::REQUEST_BECOME_PI)) {
                $SQL->removeRequest($USER->uid, UnitySQL::REQUEST_BECOME_PI);
            }
        }
    }

    public function testRequestBecomePiUserRequestedAccountDeletion()
    {
        global $USER, $SQL;
        switchUser(...getUserNotPiNotRequestedBecomePiRequestedAccountDeletion());
        $this->assertFalse($USER->isPI());
        $this->assertNumberPiBecomeRequests(0);
        $this->assertTrue($SQL->accDeletionRequestExists($USER->uid));
        try {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "pi_request",
                "tos" => "agree",
                "account_policy" => "agree",
            ]);
            $this->assertNumberPiBecomeRequests(0);
        } finally {
            if ($SQL->requestExists($USER, UnitySQL::REQUEST_BECOME_PI)) {
                $SQL->removeRequest($USER->uid, UnitySQL::REQUEST_BECOME_PI);
            }
        }
    }
}
