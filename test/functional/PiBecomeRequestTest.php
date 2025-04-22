<?php

use PHPUnit\Framework\TestCase;

class PiBecomeRequestTest extends TestCase
{
    private function assertNumberPiBecomeRequests(int $x)
    {
        global $USER, $SQL;
        if ($x == 0) {
            $this->assertFalse($SQL->requestExists($USER->getUID()));
        } elseif ($x > 0) {
            $this->assertTrue($SQL->requestExists($USER->getUID()));
        } else {
            throw new RuntimeError("x must not be negative");
        }
        $this->assertEquals($x, $this->getNumberPiBecomeRequests());
    }

    private function getNumberPiBecomeRequests()
    {
        global $USER, $SQL;
        // FIXME table name, "admin" are private constants in UnitySQL
        // FIXME "admin" should be something else
        $stmt = $SQL->getConn()->prepare(
            "SELECT * FROM requests WHERE uid=:uid and request_for='admin'"
        );
        $uid = $USER->getUID();
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
        return count($stmt->fetchAll());
    }

    public function testRequestBecomePi()
    {
        global $USER, $SQL;
        switchUser(...getUserNotPiNotRequestedBecomePi());
        error_log(json_encode($SQL->getConn()->prepare("select * from requests"))->execute()->fetchAll());
        $this->assertFalse($USER->isPI());
        $this->assertNumberPiBecomeRequests(0);
        post(
            __DIR__ . "/../../webroot/panel/account.php",
            ["form_type" => "pi_request"]
        );
        $this->assertNumberPiBecomeRequests(1);
        post(
            __DIR__ . "/../../webroot/panel/account.php",
            ["form_type" => "pi_request"]
        );
        $this->assertNumberPiBecomeRequests(1);
    }
}
