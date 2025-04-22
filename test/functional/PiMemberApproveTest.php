<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;

class PiMemberApproveTest extends TestCase {
    static $requestUid;
    static $noRequestUid;

    public static function setUpBeforeClass(): void{
        global $USER;
        switchUser(...getNormalUser());
        self::$requestUid = $USER->getUID();
        switchUser(...getNormalUser2());
        self::$noRequestUid = $USER->getUID();
    }

    private function approveUser(string $uid)
    {
        post(
            __DIR__ . "/../../webroot/panel/pi.php",
            ["form_type" => "userReq", "action" => "approve", "uid" => $uid]
        );
    }

    public function testApproveRequest()
    {
        global $USER, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $this->assertTrue($piGroup->exists());
        $this->assertEquals([$pi], $piGroup->getGroupMembers());
        $this->assertEmpty($piGroup->getRequests());
        $requestedUser = new UnityUser(self::$requestUid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        try {
            $piGroup->newUserRequest($requestedUser);
            $this->assertFalse($piGroup->userExists($requestedUser));

            $piGroup->approveUser($requestedUser);
            $this->assertEmpty($piGroup->getRequests());

            $this->assertEquals([$requestedUser, $pi], $piGroup->getGroupMembers());
            $this->assertTrue($piGroup->userExists($requestedUser));
        } finally {
            $piGroup->removeUser($requestedUser);
            $SQL->removeRequest(self::$requestUid, $piGroup->getPIUID());
        }
    }

    public function testApproveNonexistentRequest()
    {
        global $USER, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $this->assertTrue($piGroup->exists());
        $this->assertEquals([$pi], $piGroup->getGroupMembers());
        $this->assertEmpty($piGroup->getRequests());

        $notRequestedUser = new UnityUser(self::$noRequestUid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        $this->assertFalse($piGroup->userExists($notRequestedUser));
        $this->assertEmpty($piGroup->getRequests());

        try {
            $piGroup->approveUser($notRequestedUser);
            $this->assertEquals([$pi], $piGroup->getGroupMembers());
            $this->assertFalse($piGroup->userExists($notRequestedUser));
        } finally {
            $piGroup->removeUser($notRequestedUser);
        }
    }
}
