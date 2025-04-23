<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityLDAP;
use UnityWebPortal\lib\RedirectException;

class PiDisbandTest extends TestCase {
    static $requestUid;

    public static function setUpBeforeClass(): void{
        global $USER;
        switchUser(...getNormalUser());
        self::$requestUid = $USER->getUID();
    }

    private function disband()
    {
        // PI disband leads to redirect, redirect doesn't work during testing
        try {
            post(
                __DIR__ . "/../../webroot/panel/pi.php",
                ["form_name" => "disband"]
            );
        } catch (RedirectException $e) {}
    }

    public function testDisband()
    {
        global $USER, $SQL;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $piGroup->requestGroup(true);
        $piGroup->approveGroup();
        $piGroupEntry = $piGroup->getLDAPPiGroup();
        $piGroupAttributesBefore = $piGroupEntry->getAttributes();
        $piGroupName = $piGroup->getPIUID();
        $this->assertTrue($piGroup->exists());
        $this->assertEquals([$pi->getUID()], $piGroup->getGroupMemberUIDs());
        $this->assertEmpty($piGroup->getRequests());
        try {
            $SQL->addRequest(self::$requestUid, $piGroupName);
            $this->disband();
            $this->assertFalse($piGroup->exists());
            $this->assertEmpty($SQL->getRequests($piGroup->getPIUID()));
        } finally {
            $piGroupEntry->setAttributes($piGroupAttributesBefore);
            $piGroupEntry->write();
        }
    }
}
