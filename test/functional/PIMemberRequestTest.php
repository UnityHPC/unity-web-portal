<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\UnityHTTPDMessageSeverity;
use UnityWebPortal\lib\UnitySQL;

class PIMemberRequestTest extends TestCase
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
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi = $USER;
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        $this->assertTrue($USER->isPI());
        $this->assertTrue($pi_group->exists());
        $this->assertEqualsCanonicalizing([$pi], $pi_group->getGroupMembers());
        $this->assertEqualsCanonicalizing([], $SQL->getRequests($gid));
        switchUser(...getUserNotPiNotRequestedBecomePi());
        $uid = $USER->uid;
        $this->assertFalse($USER->isPI());
        $this->assertFalse($SQL->requestExists($uid, UnitySQL::REQUEST_BECOME_PI));
        $this->assertFalse($pi_group->memberExists($USER));
        try {
            $this->requestMembership($gid);
            $this->assertTrue($SQL->requestExists($uid, $gid));
            $this->cancelRequest($gid);
            $this->assertFalse($SQL->requestExists($uid, $gid));
            $this->requestMembership("asdlkjasldkj");
            assertMessageExists(
                $this,
                UnityHTTPDMessageSeverity::BAD,
                ".*",
                "This PI doesn't exist",
            );
            $this->requestMembership($pi_group->getOwner()->getMail());
            $this->assertTrue($SQL->requestExists($uid, $gid));
        } finally {
            if ($SQL->requestExists($uid, $gid)) {
                $SQL->removeRequest($uid, $gid);
            }
        }
    }
}
