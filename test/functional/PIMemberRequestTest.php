<?php

use UnityWebPortal\lib\UnityHTTPDMessageLevel;
use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UnityGroup;
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

    public function testRequestMembershipAndCancel()
    {
        global $USER, $SQL;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $this->switchUser("Blank");
        try {
            $this->requestMembership($pi_group->gid);
            $this->assertTrue($pi_group->requestExists($USER));
            $this->cancelRequest($pi_group->gid);
            $this->assertFalse($pi_group->requestExists($USER));
        } finally {
            if ($SQL->requestExists($USER->uid, $pi_group->gid)) {
                $SQL->removeRequest($USER->uid, $pi_group->gid);
            }
        }
    }

    public function testRequestMembershipBogus()
    {
        global $USER, $SQL;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $this->switchUser("Blank");
        try {
            UnityHTTPD::clearMessages();
            $this->requestMembership("asdlkjasldkj");
            $this->assertMessageExists(
                UnityHTTPDMessageLevel::ERROR,
                "/^This PI Doesn't Exist$/",
                "/.*/",
            );
            $this->assertFalse($pi_group->requestExists($USER));
        } finally {
            if ($SQL->requestExists($USER->uid, $pi_group->gid)) {
                $SQL->removeRequest($USER->uid, $pi_group->gid);
            }
        }
    }

    public function testRequestMembershipByOwnerMail()
    {
        global $USER, $SQL;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $this->switchUser("Blank");
        try {
            $this->requestMembership($pi_group->getOwner()->getMail());
            $this->assertTrue($pi_group->requestExists($USER));
        } finally {
            if ($SQL->requestExists($USER->uid, $pi_group->gid)) {
                $SQL->removeRequest($USER->uid, $pi_group->gid);
            }
        }
    }

    public function testRequestMembershipDuplicate()
    {
        global $USER, $SQL;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $this->switchUser("Blank");
        $this->assertNumberRequests(0);
        try {
            $this->requestMembership($pi_group->gid);
            $this->assertNumberRequests(1);
            // $second_request_failed = false;
            // try {
            $this->requestMembership($pi_group->gid);
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertNumberRequests(1);
        } finally {
            if ($SQL->requestExists($USER->uid, $pi_group->gid)) {
                $SQL->removeRequest($USER->uid, $pi_group->gid);
            }
        }
    }

    public function testRequestBecomePiAlreadyInGroup()
    {
        global $USER, $SQL;
        $this->switchUser("Normal");
        $pi_group_gids = $USER->getPIGroupGIDs();
        $this->assertGreaterThanOrEqual(1, count($pi_group_gids));
        $gid = $pi_group_gids[0];
        $this->assertNumberRequests(0);
        try {
            // $request_failed = false;
            // try {
            $this->requestMembership($gid);
            // } catch(Exception) {
            //     $request_failed = true;
            // }
            // $this->assertTrue($request_failed);
            $this->assertNumberRequests(0);
        } finally {
            if ($SQL->requestExists($USER->uid, $gid)) {
                $SQL->removeRequest($USER->uid, $gid);
            }
        }
    }
}
