<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\UnitySQL;

class PIBecomeRequestTest extends TestCase
{
    private function assertNumberPiBecomeRequests(int $x)
    {
        global $USER, $SQL;
        if ($x == 0) {
            $this->assertFalse($SQL->requestExists($USER->uid, "admin"));
        } elseif ($x > 0) {
            $this->assertTrue($SQL->requestExists($USER->uid, "admin"));
        } else {
            throw new RuntimeException("x must not be negative");
        }
        $this->assertEquals($x, $this->getNumberPiBecomeRequests());
    }

    private function getNumberPiBecomeRequests()
    {
        global $USER, $SQL;
        // FIXME table name, "admin" are private constants in UnitySQL
        // FIXME "admin" should be something else
        $stmt = $SQL
            ->getConn()
            ->prepare("SELECT * FROM requests WHERE uid=:uid and request_for='admin'");
        $uid = $USER->uid;
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
        return count($stmt->fetchAll());
    }

    public function testRequestBecomePi()
    {
        global $USER, $SQL;
        switchUser(...getUserNotPiNotRequestedBecomePi());
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
            if ($SQL->requestExists($USER, "admin")) {
                $SQL->removeRequest($USER->uid, "admin");
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
            if ($SQL->requestExists($USER, "admin")) {
                $SQL->removeRequest($USER->uid, "admin");
            }
        }
    }
}
