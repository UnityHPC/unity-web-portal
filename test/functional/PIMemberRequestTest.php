<?php

use UnityWebPortal\lib\UnityHTTPDMessageLevel;
use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UnityHTTPD;

class PIMemberRequestTest extends UnityWebPortalTestCase
{
    private function requestMembership(string $gid_or_mail)
    {
        http_post(__DIR__ . "/../../webroot/panel/groups.php", [
            "form_type" => "addPIform",
            "pi" => $gid_or_mail,
            "tos" => "agree",
        ]);
    }

    private function cancelRequest(string $gid)
    {
        http_post(__DIR__ . "/../../webroot/panel/groups.php", [
            "form_type" => "cancelPIForm",
            "pi" => $gid,
        ]);
    }

    public function testRequestMembership()
    {
        global $USER, $SQL;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        $this->switchUser("Blank");
        $uid = $USER->uid;
        try {
            // normal request
            $this->requestMembership($gid);
            $this->assertTrue($SQL->requestExists($uid, $gid));
            $this->cancelRequest($gid);
            $this->assertFalse($SQL->requestExists($uid, $gid));
            // bogus request
            UnityHTTPD::clearMessages();
            $this->requestMembership("asdlkjasldkj");
            $this->assertMessageExists(
                UnityHTTPDMessageLevel::ERROR,
                "/^This PI Doesn't Exist$/",
                "/.*/",
            );
            // request by mail
            $this->requestMembership($pi_group->getOwner()->getMail());
            $this->assertTrue($SQL->requestExists($uid, $gid));
            // duplicate request
            $this->requestMembership($gid);
            $this->assertTrue($SQL->requestExists($uid, $gid));
            // $second_request_failed = false;
            // try {
            $this->requestMembership($gid);
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertTrue($SQL->requestExists($uid, $gid));
            $this->cancelRequest($gid);
            $this->assertFalse($SQL->requestExists($uid, $gid));
        } finally {
            if ($SQL->requestExists($uid, $gid)) {
                $SQL->removeRequest($uid, $gid);
            }
        }
    }
}
